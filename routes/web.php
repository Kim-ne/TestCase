<?php

use App\Http\Controllers\TestCaseInputController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TestCaseInputController::class, 'index']);
Route::post('/test-case-input', [TestCaseInputController::class, 'store'])
    ->name('test-case-input');
Route::get('/test-case-input/{generationRequest}/status', [TestCaseInputController::class, 'status'])
    ->name('test-case-input.status');
