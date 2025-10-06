<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DiscordBotController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeaturedListingController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpinWheelController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CryptoPaymentController;use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'me']);

// Public platform stats
Route::get('/stats', [StatsController::class, 'getPlatformStats']);

// Public leaderboard (anyone can view rankings)
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// Public user profile (no auth required)
Route::get('/users/{username}/public', [UserController::class, 'showPublic']);

// Discord Bot API Routes (protected with custom middleware)
Route::prefix('discord-bot')->middleware('discord.bot')->group(function () {
    Route::get('/leaderboard', [DiscordBotController::class, 'getLeaderboard']);
    Route::get('/listings/recent', [DiscordBotController::class, 'getRecentListings']);
    Route::get('/users/{username}', [DiscordBotController::class, 'getUserProfile']);
    Route::get('/search', [DiscordBotController::class, 'search']);
    Route::get('/stats', [DiscordBotController::class, 'getStats']);
});

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF token set', 'token' => csrf_token()]);
});

Route::middleware(['auth:sanctum'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/me', [UserController::class, 'me'])->name('me');
    Route::get('/me/subscriptions', [UserController::class, 'getMySubscriptions'])->name('mySubscriptions');
    Route::get('/me/stats', [UserStatsController::class, 'getUserStats'])->name('myStats');
    Route::put('/edit', [UserController::class, 'edit'])->name('edit');
    Route::post('/become-seller', [UserController::class, 'becomeSeller'])->name('becomeSeller');
    Route::get('/provider-games', [UserController::class, 'getProviderGames'])->name('providerGames');
    Route::post('/provider-games', [UserController::class, 'addProviderGame'])->name('addProviderGame');
    Route::delete('/provider-games/{providerId}', [UserController::class, 'removeProviderGame'])->name('removeProviderGame');
    // This wildcard route must be last to avoid catching specific routes above
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
});

Route::middleware(["auth:sanctum"])->prefix("stripe")->name("stripe.")->group(function () {
    // Stripe API routes
    Route::post("/create-payment-intent", [PaymentController::class, "createPaymentIntent"]);
    Route::get("/payment-intent/{paymentIntentId}", [StripePaymentController::class, "getPaymentIntent"]);
    // Subscription routes
    Route::post("/subscribe", [PaymentController::class, "createSubscriptionCheckoutSession"]);

    // Stripe Connect routes
    Route::prefix("connect")->name("connect.")->group(function () {
        Route::post("/account-link", [StripeConnectController::class, "createAccountLink"]);
        Route::get("/account-status", [StripeConnectController::class, "getAccountStatus"]);
        Route::post("/dashboard-link", [StripeConnectController::class, "createDashboardLink"]);
        Route::post("/payout", [StripeConnectController::class, "requestPayout"]);
        Route::get("/payouts", [StripeConnectController::class, "getPayouts"]);
        Route::post("/disconnect", [StripeConnectController::class, "disconnect"]);
    });
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
    Route::post('/orders/{orderId}/items/{itemId}/confirm-delivery', [OrderController::class, 'confirmDelivery']);

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

    // Subscription Management (Cashier) - Unified for all users
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions/tiers', [SubscriptionController::class, 'getTiers']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
    Route::post('/subscriptions/setup-intent', [SubscriptionController::class, 'setupIntent']);
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/subscriptions/invoices', [SubscriptionController::class, 'invoices']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscriptions/resume', [SubscriptionController::class, 'resume']);
    Route::put('/subscriptions/{id}/auto-renew', [SubscriptionController::class, 'updateAutoRenew']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);

    // Provider Tier Progress (volume-based)
    Route::get('/provider/tier-progress', [UserController::class, 'getTierProgress']);

    // Leaderboard history (requires auth to see your own history)
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

    // Referral System
    Route::get('/referrals/stats', [ReferralController::class, 'getStats']);
    Route::get('/referrals/list', [ReferralController::class, 'getReferrals']);
    Route::get('/referrals/earnings', [ReferralController::class, 'getEarnings']);
    Route::post('/referrals/apply', [ReferralController::class, 'applyReferralCode']);

});

// Image uploads (accessible without Sanctum auth for Filament admin)
Route::post('/upload/image', [UploadController::class, 'uploadImage']);
Route::post('/upload/images', [UploadController::class, 'uploadMultipleImages']);
Route::post('/upload/game-logo', [UploadController::class, 'uploadGameLogo']);
Route::post('/upload/game-icon', [UploadController::class, 'uploadGameIcon']);

// Authenticated file deletion
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/upload/image', [UploadController::class, 'deleteImage']);
});

// Seasons (Public endpoints)
Route::get('/seasons/current', [SeasonController::class, 'current']);
Route::get('/seasons', [SeasonController::class, 'index']);
Route::get('/seasons/{id}', [SeasonController::class, 'show']);
Route::get('/seasons/{id}/stats', [SeasonController::class, 'stats']);
Route::get('/seasons/{id}/leaderboard', [SeasonController::class, 'leaderboard']);
Route::get('/users/{userId}/seasons', [SeasonController::class, 'userSeasons']);

// Public featured listings endpoints
Route::get('/featured-listings/pricing', [FeaturedListingController::class, 'getPricing']);
Route::get('/featured-listings/active', [FeaturedListingController::class, 'getActive']);

// Public referral endpoints
Route::post('/referrals/validate', [ReferralController::class, 'validateCode']);
Route::get('/referrals/leaderboard', [ReferralController::class, 'getLeaderboard']);

// Admin routes (TODO: Add admin middleware)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/sellers/custom-earnings', [AdminController::class, 'getCustomEarningsSellers']);
    Route::post('/sellers/{userId}/earnings', [AdminController::class, 'setSellerEarnings']);
    Route::delete('/sellers/{userId}/earnings', [AdminController::class, 'resetSellerEarnings']);
    Route::post('/users/{userId}/grant-subscription', [AdminController::class, 'grantSubscription']);
});

Route::group(['prefix' => 'games'], function () {
    Route::get('/', [GameController::class, 'index'])->name('games.index');
    Route::get('/{id}', [GameController::class, 'show'])->name('games.show');
});

Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/{id}', [ServiceController::class, 'show'])->name('services.show');
    Route::post('/', [ServiceController::class, 'store'])->middleware('auth:sanctum')->name('services.store');
});

Route::group(['prefix' => 'items'], function () {
    Route::get('/', [ItemController::class, 'index'])->name('items.index');
    Route::get('/{id}', [ItemController::class, 'show'])->name('items.show');
    Route::post('/', [ItemController::class, 'store'])->middleware('auth:sanctum')->name('items.store');
});

Route::group(['prefix' => 'currencies'], function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('/{id}', [CurrencyController::class, 'show'])->name('currencies.show');
    Route::post('/', [CurrencyController::class, 'store'])->middleware('auth:sanctum')->name('currencies.store');
});

Route::group(['prefix' => 'accounts'], function () {
    Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/{id}', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('/', [AccountController::class, 'store'])->middleware('auth:sanctum')->name('accounts.store');
});

Route::group(['prefix' => 'advertisements'], function () {
    Route::get('/', [AdvertisementController::class, 'index'])->name('advertisements.index');
});

Route::group(['prefix' => 'providers'], function () {
    Route::get('/', [ProviderController::class, 'index'])->name('providers.index');
    Route::get('/{id}', [ProviderController::class, 'show'])->name('providers.show');
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
// Handles both custom payment intents and Cashier subscription events
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Crypto payment routes
Route::middleware(['auth:sanctum'])->prefix('crypto')->name('crypto.')->group(function () {
    Route::get('/currencies', [CryptoPaymentController::class, 'getAvailableCurrencies']);
    Route::get('/min-amount', [CryptoPaymentController::class, 'getMinimumAmount']);
    Route::post('/deposit', [CryptoPaymentController::class, 'createDeposit']);
    Route::post('/payout', [CryptoPaymentController::class, 'createPayout']);
    Route::get('/payment/{paymentId}', [CryptoPaymentController::class, 'getPaymentStatus']);
    Route::get('/transactions', [CryptoPaymentController::class, 'getTransactions']);
});

// Crypto webhooks (no auth required)
Route::post('/crypto/webhook', [CryptoPaymentController::class, 'handleWebhook']);
Route::post('/crypto/payout-webhook', [CryptoPaymentController::class, 'handleWebhook']);
