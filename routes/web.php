<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;

Route::get('/', [TransferController::class, 'index'])->name('home');
Route::post('/transfer', [TransferController::class, 'transfer'])->name('transfer');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/disconnect', [AuthController::class, 'disconnect'])->name('auth.disconnect');

// Subscription routes
Route::get('/pricing', [SubscriptionController::class, 'pricing'])->name('subscription.pricing');
Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe')->middleware('auth');
Route::get('/subscription/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage')->middleware('auth');
Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel')->middleware('auth');
Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade')->middleware('auth');

// Payment provider callbacks
Route::get('/paystack/callback', [SubscriptionController::class, 'paystackCallback'])->name('paystack.callback');
Route::get('/lemonsqueezy/success', [SubscriptionController::class, 'lemonSqueezySuccess'])->name('lemonsqueezy.success');

// Webhooks (no middleware - external providers)
Route::post('/webhooks/paystack', [SubscriptionController::class, 'paystackWebhook'])->name('webhooks.paystack');
Route::post('/webhooks/lemonsqueezy', [SubscriptionController::class, 'lemonSqueezyWebhook'])->name('webhooks.lemonsqueezy');
