<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeaturedListingController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SellerSubscriptionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpinWheelController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'me']);

// Public platform stats
Route::get('/stats', [StatsController::class, 'getPlatformStats']);

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF token set', 'token' => csrf_token()]);
});

Route::middleware(['auth:sanctum'])->prefix('users')->name('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('.index');
    Route::get('/me', [UserController::class, 'me'])->name('.me');
    Route::get('/me/subscriptions', [UserController::class, 'getMySubscriptions'])->name('.mySubscriptions');
    Route::get('/me/stats', [UserStatsController::class, 'getUserStats'])->name('.myStats');
    Route::get('/{user}', [UserController::class, 'show'])->name('.show');
    Route::put('/edit', [UserController::class, 'edit'])->name('.edit');
});

Route::middleware(['auth:sanctum'])->prefix('stripe')->name('stripe.')->group(function () {
    // Stripe API routes
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::get('/payment-intent/{paymentIntentId}', [StripePaymentController::class, 'getPaymentIntent']);
    // Subscription routes
    Route::post('/subscribe', [PaymentController::class, 'createSubscriptionCheckoutSession']);
    Route::post('/cancel-subscription', [SubscriptionController::class, 'cancelSubscription']);
    Route::get('/subscription-status', [SubscriptionController::class, 'subscriptionStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/remove', [CartController::class, 'remove']);
    Route::post('/cart/update', [CartController::class, 'update']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/seller/all', [OrderController::class, 'sellerOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{orderId}/items/{itemId}/deliver', [OrderController::class, 'deliverItem']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
    Route::get('/wallet/withdrawals', [WalletController::class, 'withdrawalRequests']);
    Route::post('/wallet/withdrawals/{id}/cancel', [WalletController::class, 'cancelWithdrawal']);

    // Subscription Management (Cashier)
    Route::get('/subscription/current', [SubscriptionController::class, 'current']);
    Route::get('/subscription/invoices', [SubscriptionController::class, 'invoices']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume']);

    // Seller Subscriptions (now for features only, not tier upgrades)
    Route::get('/seller-subscriptions/tiers', [SellerSubscriptionController::class, 'getTiers']);
    Route::get('/seller-subscriptions/current', [SellerSubscriptionController::class, 'getCurrent']);
    Route::post('/seller-subscriptions', [SellerSubscriptionController::class, 'subscribe']);
    Route::delete('/seller-subscriptions', [SellerSubscriptionController::class, 'cancel']);

    // Provider Tier Progress (volume-based)
    Route::get('/provider/tier-progress', [UserController::class, 'getTierProgress']);

    // Leaderboard with Rewards
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/leaderboard/history', [LeaderboardController::class, 'userHistory']);

    // Featured Listings
    Route::get('/featured-listings', [FeaturedListingController::class, 'index']);
    Route::post('/featured-listings', [FeaturedListingController::class, 'store']);
    Route::delete('/featured-listings/{id}', [FeaturedListingController::class, 'destroy']);

    // Messaging
    Route::get('/messages/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/conversations/{conversationId}', [MessageController::class, 'getMessages']);
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
    Route::post('/messages/start', [MessageController::class, 'startConversation']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);

    // Achievements
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/achievements/category/{category}', [AchievementController::class, 'byCategory']);
    Route::get('/achievements/my', [AchievementController::class, 'userAchievements']);
    Route::get('/achievements/stats', [AchievementController::class, 'userStats']);
    Route::post('/achievements/check', [AchievementController::class, 'checkUnlockable']);
    Route::post('/achievements/{achievementId}/claim', [AchievementController::class, 'claimReward']);
    Route::get('/achievements/recent', [AchievementController::class, 'recent']);

    // Events
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/active', [EventController::class, 'active']);
    Route::get('/events/upcoming', [EventController::class, 'upcoming']);
    Route::get('/events/featured', [EventController::class, 'featured']);
    Route::get('/events/my', [EventController::class, 'userEvents']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::post('/events/{id}/register', [EventController::class, 'register']);
    Route::post('/events/{id}/claim-prize', [EventController::class, 'claimPrize']);
    Route::get('/events/{id}/leaderboard', [EventController::class, 'leaderboard']);

    // Spin Wheel
    Route::get('/spin-wheels', [SpinWheelController::class, 'index']);
    Route::get('/spin-wheels/history', [SpinWheelController::class, 'history']);
    Route::get('/spin-wheels/{wheelId}', [SpinWheelController::class, 'show']);
    Route::post('/spin-wheels/{wheelId}/spin', [SpinWheelController::class, 'spin']);

    // File Uploads
    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::post('/upload/images', [UploadController::class, 'uploadMultipleImages']);
    Route::delete('/upload/image', [UploadController::class, 'deleteImage']);
});

// Public featured listings endpoints
Route::get('/featured-listings/pricing', [FeaturedListingController::class, 'getPricing']);
Route::get('/featured-listings/active', [FeaturedListingController::class, 'getActive']);

// Admin routes (TODO: Add admin middleware)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/creators/custom-earnings', [AdminController::class, 'getCustomEarningsCreators']);
    Route::post('/creators/{userId}/earnings', [AdminController::class, 'setCreatorEarnings']);
    Route::delete('/creators/{userId}/earnings', [AdminController::class, 'resetCreatorEarnings']);
});

Route::group(['prefix' => 'games'], function () {
    Route::get('/', [GameController::class, 'index'])->name('.index');
    Route::get('/{id}', [GameController::class, 'show'])->name('.show');
});

Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index'])->name('.index');
    Route::get('/{id}', [ServiceController::class, 'show'])->name('.show');
    Route::post('/', [ServiceController::class, 'store'])->middleware('auth:sanctum')->name('.store');
});

Route::group(['prefix' => 'items'], function () {
    Route::get('/', [ItemController::class, 'index'])->name('.index');
    Route::get('/{id}', [ItemController::class, 'show'])->name('.show');
    Route::post('/', [ItemController::class, 'store'])->middleware('auth:sanctum')->name('.store');
});

Route::group(['prefix' => 'currencies'], function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('.index');
    Route::get('/{id}', [CurrencyController::class, 'show'])->name('.show');
    Route::post('/', [CurrencyController::class, 'store'])->middleware('auth:sanctum')->name('.store');
});

Route::group(['prefix' => 'accounts'], function () {
    Route::get('/', [AccountController::class, 'index'])->name('.index');
    Route::get('/{id}', [AccountController::class, 'show'])->name('.show');
    Route::post('/', [AccountController::class, 'store'])->middleware('auth:sanctum')->name('.store');
});

Route::group(['prefix' => 'advertisements'], function () {
    Route::get('/', [AdvertisementController::class, 'index'])->name('.index');
});

Route::group(['prefix' => 'providers'], function () {
    Route::get('/', [ProviderController::class, 'index'])->name('.index');
    Route::get('/{id}', [ProviderController::class, 'show'])->name('.show');
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

Route::prefix('auth')->group(function () {
    Route::get('discord', [AuthController::class, 'redirectToProvider']);
    Route::get('discord/callback', [AuthController::class, 'handleProviderCallback']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Email verification
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification', [AuthController::class, 'resendVerification']);

    // Password reset
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Webhook route (no CSRF protection needed)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
