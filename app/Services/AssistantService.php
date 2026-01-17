<?php

namespace App\Services;

use App\Events\AssistantMessage;
use App\Models\Product;
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

class AssistantService
{
    protected array $functionDeclarations = [];

    public function __construct()
    {
        $this->buildFunctionDeclarations();
    }

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

    public function processPrompt(string $prompt): string
    {
        $chat = Gemini::generativeModel(model: 'gemini-2.0-flash')
            ->withTool(new Tool(functionDeclarations: $this->functionDeclarations))
            ->startChat();

        $this->broadcastMessage('Processing your request...');

        $response = $chat->sendMessage($prompt);

        while ($response->parts()[0]->functionCall !== null) {
            $functionCall = $response->parts()[0]->functionCall;
            $this->broadcastMessage("Executing: {$functionCall->name}");

            $functionResponse = $this->handleFunctionCall($functionCall);
            $response = $chat->sendMessage($functionResponse);
        }

        $finalText = $response->text();
        $this->broadcastMessage($finalText);

        return $finalText;
    }

    protected function handleFunctionCall(FunctionCall $functionCall): Content
    {
        $result = match ($functionCall->name) {
            'list_products' => $this->listProducts($functionCall->args),
            'get_product' => $this->getProduct($functionCall->args),
            'create_product' => $this->createProduct($functionCall->args),
            'update_product' => $this->updateProduct($functionCall->args),
            'delete_product' => $this->deleteProduct($functionCall->args),
            default => ['error' => 'Unknown function'],
        };

        return new Content(
            parts: [
                new Part(
                    functionResponse: new FunctionResponse(
                        name: $functionCall->name,
                        response: $result
                    )
                ),
            ],
            role: Role::USER
        );
    }

    protected function listProducts(array $args): array
    {
        $query = Product::query();

        if (isset($args['category'])) {
            $query->where('category', $args['category']);
        }

        return ['products' => $query->get()->toArray()];
    }

    protected function getProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (! $product) {
            return ['error' => 'Product not found'];
        }

        return ['product' => $product->toArray()];
    }

    protected function createProduct(array $args): array
    {
        $product = Product::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'price' => $args['price'],
            'category' => $args['category'],
        ]);

        return ['product' => $product->toArray(), 'message' => 'Product created successfully'];
    }

    protected function updateProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (! $product) {
            return ['error' => 'Product not found'];
        }

        $product->update(array_filter([
            'name' => $args['name'] ?? null,
            'description' => $args['description'] ?? null,
            'price' => $args['price'] ?? null,
            'category' => $args['category'] ?? null,
        ]));

        return ['product' => $product->fresh()->toArray(), 'message' => 'Product updated successfully'];
    }

    protected function deleteProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (! $product) {
            return ['error' => 'Product not found'];
        }

        $product->delete();

        return ['message' => 'Product deleted successfully'];
    }

    protected function broadcastMessage(string $message): void
    {
        broadcast(new AssistantMessage($message));
    }
}
