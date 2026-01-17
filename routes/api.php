<?php

use App\Events\TestEvent;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('products', ProductController::class);

Route::post('/assistant', [AssistantController::class, 'process']);

Route::get('/test-gemini', function () {
    $result = \Gemini\Laravel\Facades\Gemini::generativeModel(model: 'gemini-2.0-flash')
        ->generateContent('Say hello');

    return response()->json([
        'success' => true,
        'response' => $result->text(),
    ]);
});

Route::post('/test-event', function () {
    broadcast(new TestEvent('Hello from Laravel!'));

    return response()->json(['status' => 'Event broadcasted']);
});
