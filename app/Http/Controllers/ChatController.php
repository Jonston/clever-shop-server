<?php

namespace App\Http\Controllers;

use App\Services\ChatAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(protected ChatAssistantService $chatService)
    {
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:10000',
            'conversation_id' => 'nullable|exists:conversations,id',
            'session_id' => 'nullable|string',
        ]);

        try {
            $result = $this->chatService->processMessage(
                prompt: $validated['prompt'],
                conversationId: $validated['conversation_id'] ?? null,
                userId: $request->user()?->id,
                sessionId: $validated['session_id'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

