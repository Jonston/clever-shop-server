<?php

namespace App\Services;

use App\Events\AssistantIterationComplete;
use App\Events\AssistantMessageComplete;
use App\Events\AssistantProcessing;
use App\Models\Conversation;
use App\Models\FunctionExecution;
use App\Models\Message;
use Gemini\Data\Content;
use Gemini\Data\FunctionCall;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\FunctionResponse;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\Tool;
use Gemini\Enums\DataType;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class ChatAssistantService
{
    protected array $functionDeclarations = [];

    protected array $functionHandlers = [];

    public function __construct(protected ProductService $productService)
    {
        $this->buildFunctionDeclarations();
        $this->registerFunctionHandlers();
    }

    /**
     * Process a message in a conversation with parallel function calling support
     */
    public function processMessage(
        string $prompt,
        ?int $conversationId = null,
        ?int $userId = null,
        ?string $sessionId = null
    ): array {
        $startTime = microtime(true);

        // Get or create conversation
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
            if (! $conversation) {
                throw new \Exception('Conversation not found');
            }
            $this->validateAccess($conversation, $userId, $sessionId);
        } else {
            $conversation = $this->getOrCreateConversation($userId, $sessionId);
        }

        // Create user message
        $userMessage = Message::createUserMessage($conversation->id, $prompt);

        // Update conversation last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Generate title if not exists
        if (! $conversation->title) {
            $conversation->generateTitle();
        }

        // Build context from conversation history
        $context = $this->buildContext($conversation);

        // Broadcast processing started
        broadcast(new AssistantProcessing(
            $conversation->id,
            'started',
            'Processing your request...'
        ));

        // Start chat with Gemini
        $chat = Gemini::generativeModel(model: 'gemini-2.0-flash')
            ->withTool(new Tool(functionDeclarations: $this->functionDeclarations))
            ->startChat(history: $this->convertContextToHistory($context));

        // Send the new message
        $response = $chat->sendMessage($prompt);

        // Create assistant message placeholder
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
        ]);

        // Handle function calling loop with parallel support
        $maxIterations = 20;
        $iteration = 0;

        while ($this->hasFunctionCalls($response) && $iteration < $maxIterations) {
            $iteration++;
            $iterationStart = microtime(true);

            Log::info("Iteration {$iteration}: Processing function calls", [
                'conversation_id' => $conversation->id,
            ]);

            broadcast(new AssistantProcessing(
                $conversation->id,
                'processing',
                "Executing functions (iteration {$iteration})..."
            ));

            // Extract ALL function calls from response
            $functionCalls = $this->extractFunctionCalls($response);
            $functionResponses = [];

            // Execute all function calls (parallel execution)
            foreach ($functionCalls as $functionCall) {
                Log::info("Executing function: {$functionCall->name}");

                $execution = $this->executeFunctionWithLogging($functionCall, $assistantMessage->id);

                $functionResponses[] = new Part(
                    functionResponse: new FunctionResponse(
                        name: $functionCall->name,
                        response: $execution->result
                    )
                );
            }

            // Send ALL function responses back to Gemini at once
            $response = $chat->sendMessage(new Content(
                parts: $functionResponses,
                role: Role::USER
            ));

            $iterationTime = (int) ((microtime(true) - $iterationStart) * 1000);

            broadcast(new AssistantIterationComplete(
                $conversation->id,
                $iteration,
                implode(', ', array_map(fn ($fc) => $fc->name, $functionCalls)),
                $iterationTime
            ));
        }

        if ($iteration >= $maxIterations) {
            Log::warning("Max iterations reached for conversation {$conversation->id}");
        }

        // Get final text response
        $finalText = $response->text();

        // Update assistant message with final content
        $processingTime = (int) ((microtime(true) - $startTime) * 1000);
        $assistantMessage->update([
            'content' => $finalText,
            'processing_time_ms' => $processingTime,
            'tokens_used' => $response->usageMetadata?->totalTokenCount ?? null,
        ]);

        // Update conversation last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Broadcast completion
        broadcast(new AssistantMessageComplete(
            $conversation->id,
            $finalText,
            $assistantMessage->id
        ));

        return [
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'message' => $finalText,
            'message_id' => $assistantMessage->id,
            'processing_time_ms' => $processingTime,
            'iterations' => $iteration,
        ];
    }

    /**
     * Check if response has function calls
     */
    protected function hasFunctionCalls($response): bool
    {
        $parts = $response->parts();
        if (empty($parts)) {
            return false;
        }

        foreach ($parts as $part) {
            if ($part->functionCall !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract ALL function calls from response parts
     */
    protected function extractFunctionCalls($response): array
    {
        $functionCalls = [];
        $parts = $response->parts();

        foreach ($parts as $part) {
            if ($part->functionCall !== null) {
                $functionCalls[] = $part->functionCall;
            }
        }

        return $functionCalls;
    }

    /**
     * Get or create conversation for user or guest
     */
    protected function getOrCreateConversation(?int $userId, ?string $sessionId): Conversation
    {
        return Conversation::findOrCreateForOwner($userId, $sessionId);
    }

    /**
     * Validate access to conversation
     */
    protected function validateAccess(Conversation $conversation, ?int $userId, ?string $sessionId): void
    {
        if ($conversation->user_id && $conversation->user_id !== $userId) {
            throw new \Exception('Unauthorized access to conversation');
        }

        if ($conversation->session_id && $conversation->session_id !== $sessionId && ! $userId) {
            throw new \Exception('Unauthorized access to conversation');
        }
    }

    /**
     * Build context from conversation
     */
    protected function buildContext(Conversation $conversation): array
    {
        return $conversation->getContext(20);
    }

    /**
     * Convert context array to Gemini history format
     */
    protected function convertContextToHistory(array $context): array
    {
        $history = [];

        foreach ($context as $message) {
            $history[] = new Content(
                parts: [new Part(text: $message['content'])],
                role: $message['role'] === 'user' ? Role::USER : Role::MODEL
            );
        }

        return $history;
    }

    /**
     * Execute function with logging
     */
    protected function executeFunctionWithLogging(FunctionCall $functionCall, int $messageId): FunctionExecution
    {
        $startTime = microtime(true);

        // Create execution record
        $execution = FunctionExecution::create([
            'message_id' => $messageId,
            'function_name' => $functionCall->name,
            'arguments' => $functionCall->args,
            'status' => 'pending',
        ]);

        try {
            $result = $this->executeFunction($functionCall);

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            $execution->update([
                'result' => $result,
                'status' => 'success',
                'execution_time_ms' => $executionTime,
            ]);
        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'result' => ['error' => $e->getMessage()],
            ]);
        }

        return $execution->fresh();
    }

    /**
     * Execute a function call
     */
    protected function executeFunction(FunctionCall $functionCall): array
    {
        if (! isset($this->functionHandlers[$functionCall->name])) {
            return ['error' => 'Unknown function'];
        }

        $handler = $this->functionHandlers[$functionCall->name];

        return $handler($functionCall->args);
    }

    /**
     * Build function declarations from config
     */
    protected function buildFunctionDeclarations(): void
    {
        $functions = config('assistant.functions', []);

        foreach ($functions as $functionConfig) {
            $properties = [];
            $required = [];

            if (isset($functionConfig['parameters']['properties'])) {
                foreach ($functionConfig['parameters']['properties'] as $name => $prop) {
                    $properties[$name] = new Schema(
                        type: DataType::from(strtoupper($prop['type'])),
                        description: $prop['description'] ?? null
                    );
                }
            }

            if (isset($functionConfig['parameters']['required'])) {
                $required = $functionConfig['parameters']['required'];
            }

            $this->functionDeclarations[] = new FunctionDeclaration(
                name: $functionConfig['name'],
                description: $functionConfig['description'],
                parameters: new Schema(
                    type: DataType::OBJECT,
                    properties: $properties,
                    required: $required
                )
            );
        }
    }

    /**
     * Register function handlers
     */
    protected function registerFunctionHandlers(): void
    {
        $this->functionHandlers = [
            'list_products' => fn ($args) => $this->productService->listProducts($args),
            'get_product' => fn ($args) => $this->productService->getProduct($args),
            'create_product' => fn ($args) => $this->productService->createProduct($args),
            'update_product' => fn ($args) => $this->productService->updateProduct($args),
            'delete_product' => fn ($args) => $this->productService->deleteProduct($args),
        ];
    }
}
