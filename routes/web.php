<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\StreamProgressController;

Route::get('/', [TransferController::class, 'index'])->name('home');
Route::post('/transfer', [TransferController::class, 'transfer'])->name('transfer');

// Progress tracking for streaming transfers
Route::get('/transfer/progress', [StreamProgressController::class, 'streamProgress'])->name('transfer.progress');

// SEO Landing Pages
Route::get('/wetransfer-pricing', [SeoController::class, 'pricing'])->name('seo.pricing');
Route::get('/wetransfer-send-files', [SeoController::class, 'sendFiles'])->name('seo.send-files');
Route::get('/wetransfer-upload', [SeoController::class, 'upload'])->name('seo.upload');
Route::get('/wetransfer-free', [SeoController::class, 'free'])->name('seo.free');
Route::get('/wetransfer-alternative', [SeoController::class, 'alternative'])->name('seo.alternative');
Route::get('/save-to-google-drive', [SeoController::class, 'googleDriveGuide'])->name('seo.google-drive-guide');

// Support Pages
Route::get('/help', [SupportController::class, 'help'])->name('support.help');
Route::get('/contact', [SupportController::class, 'contact'])->name('support.contact');
Route::get('/privacy', [SupportController::class, 'privacy'])->name('support.privacy');
Route::get('/terms', [SupportController::class, 'terms'])->name('support.terms');

// Sitemap route
Route::get('/sitemap.xml', function () {
    return response()->file(public_path('sitemap.xml'));
})->name('sitemap');

// Robots.txt route
Route::get('/robots.txt', function () {
    return response()->file(public_path('robots.txt'));
})->name('robots');

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

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'userDetail'])->name('users.detail');
    Route::post('/users/{user}/make-admin', [AdminController::class, 'makeAdmin'])->name('users.make-admin');
    Route::post('/users/{user}/remove-admin', [AdminController::class, 'removeAdmin'])->name('users.remove-admin');
    Route::post('/users/{user}/grant-trial', [AdminController::class, 'grantTrial'])->name('users.grant-trial');
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
});
