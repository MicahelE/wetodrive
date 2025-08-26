<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\AuthController;

Route::get('/', [TransferController::class, 'index'])->name('home');
Route::post('/transfer', [TransferController::class, 'transfer'])->name('transfer');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/disconnect', [AuthController::class, 'disconnect'])->name('auth.disconnect');
