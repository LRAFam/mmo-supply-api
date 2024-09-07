<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('users')->name('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('.index');
    Route::get('/me', [UserController::class, 'getMe'])->name('.me');
    Route::get('/me/subscriptions', [UserController::class, 'getMySubscriptions'])->name('.mySubscriptions');
    Route::get('/{user}', [UserController::class, 'show'])->name('.show');
    Route::put('/edit', [UserController::class, 'edit'])->name('.edit');
});

Route::middleware(['auth:sanctum'])->prefix('stripe')->name('stripe.')->group(function () {
    // Stripe API routes
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    // Subscription routes
    Route::post('/subscribe', [PaymentController::class, 'createSubscriptionCheckoutSession']);
    Route::post('/cancel-subscription', [SubscriptionController::class, 'cancelSubscription']);
    Route::get('/subscription-status', [SubscriptionController::class, 'subscriptionStatus']);
});

Route::group(['prefix' => 'games'], function () {
    Route::get('/', [GameController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'items'], function () {
    Route::get('/', [ItemController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'currencies'], function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'accounts'], function () {
    Route::get('/', [AccountController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'advertisements'], function () {
    Route::get('/', [AdvertisementController::class, 'index'])->name('.index');
});

//Route::group(['prefix' => 'posts'], function () {
//    Route::get('/', [PostController::class, 'index'])->name('posts.index');
//    Route::get('/{post}', [PostController::class, 'show'])->name('posts.show');
//});
//
//Route::group(['prefix' => 'updates'], function () {
//    Route::get('/', [UpdateController::class, 'index'])->name('updates.index');
//    Route::get('/{update}', [UpdateController::class, 'show'])->name('updates.show');
//});
//
//Route::group(['prefix' => 'products'], function () {
//    Route::get('/', [ProductController::class, 'index'])->name('products.index');
//});

Route::middleware(['web'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('discord', [AuthController::class, 'redirectToProvider']);
        Route::get('discord/callback', [AuthController::class, 'handleProviderCallback']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Webhook route
//Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook']);
