<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function __construct(
        protected AssistantService $assistantService
    ) {}

    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $result = $this->assistantService->processPrompt($request->input('prompt'));

        return response()->json([
            'response' => $result,
        ]);
    }
}
