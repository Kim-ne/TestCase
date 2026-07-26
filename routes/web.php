<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestCaseInputController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/test-case-input', [TestCaseInputController::class, 'store'])
        ->name('test-case-input');
