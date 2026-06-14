<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;

Route::post('/loans', [LoanController::class, 'store']);
Route::put('/loans/{id}/return', [LoanController::class, 'returnBook']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
