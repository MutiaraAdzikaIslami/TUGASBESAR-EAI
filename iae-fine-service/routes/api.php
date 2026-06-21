<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FineController;

Route::get('/fines', [FineController::class, 'index']);
Route::get('/fines/info', [FineController::class, 'info']);
Route::get('/fines/user/{userId}', [FineController::class, 'userFines']);
Route::get('/fines/user/{userId}/unpaid', [FineController::class, 'userUnpaidFines']);
Route::get('/fines/{id}', [FineController::class, 'show']);
Route::put('/fines/{id}/pay', [FineController::class, 'pay']);
