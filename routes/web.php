<?php

use App\Http\Controllers\TestCaseInputController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('test-case-input');
});
Route::post('/test-case-input', [TestCaseInputController::class, 'store'])
    ->name('test-case-input');
