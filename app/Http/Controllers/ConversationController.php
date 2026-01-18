<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'nullable|string',
        ]);

        $conversations = Conversation::getForOwner(
            $request->user()?->id,
            $validated['session_id'] ?? null
        );

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::with('messages')->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Validate access
        if ($conversation->user_id && $conversation->user_id !== $request->user()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sessionId = $request->input('session_id');
        if ($conversation->session_id && $conversation->session_id !== $sessionId && ! $request->user()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $sessionId = null;
        $userId = $request->user()?->id;

        if (! $userId) {
            $sessionId = Conversation::generateSessionId();
        }

        $conversation = Conversation::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'title' => $validated['title'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'conversation' => $conversation,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $conversation = Conversation::find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $conversation->update(['status' => 'deleted']);
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully',
        ]);
    }

    public function archive(int $id): JsonResponse
    {
        $conversation = Conversation::find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $conversation->update(['status' => 'archived']);

        return response()->json([
            'message' => 'Conversation archived successfully',
            'conversation' => $conversation,
        ]);
    }
}
