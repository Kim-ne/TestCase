<?php

use App\Http\Controllers\Api\TestCaseGenerationController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is healthy',
        'version' => '1.0.0',
    ]);
});

Route::post('/test-cases/generate', TestCaseGenerationController::class)
    ->name('api.test-cases.generate');          //post      api/test-cases/generate
