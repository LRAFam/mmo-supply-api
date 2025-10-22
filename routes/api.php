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
use App\Http\Controllers\NotificationController;
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
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CryptoPaymentController;
use App\Http\Controllers\PayPalPayoutController;
use App\Http\Controllers\PayPalCheckoutController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

// Broadcasting authentication for token-based auth
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    $user = $request->user();

    // Log comprehensive debug info
    \Log::info('[Broadcasting Auth] Request received', [
        'user_id' => $user?->id,
        'channel_name' => $request->input('channel_name'),
        'socket_id' => $request->input('socket_id'),
        'has_auth_header' => $request->hasHeader('Authorization'),
        'auth_header' => $request->hasHeader('Authorization') ? substr($request->header('Authorization'), 0, 30) . '...' : 'MISSING',
    ]);

    if (!$user) {
        \Log::error('[Broadcasting Auth] No authenticated user found');
        return response()->json([
            'message' => 'Unauthenticated',
            'error' => 'No user found in request after auth:sanctum middleware'
        ], 401);
    }

    try {
        $response = Broadcast::auth($request);
        \Log::info('[Broadcasting Auth] Success', ['user_id' => $user->id]);
        return $response;
    } catch (\Exception $e) {
        \Log::error('[Broadcasting Auth] Exception', [
            'user_id' => $user->id,
            'channel' => $request->input('channel_name'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'Channel authorization failed',
            'error' => $e->getMessage(),
        ], 403);
    }
});

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'me']);

// Public platform stats
Route::get('/stats', [StatsController::class, 'getPlatformStats']);

// Public leaderboard (anyone can view rankings)
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// Discord Bot API Routes (protected with custom middleware)
Route::prefix('discord-bot')->middleware('discord.bot')->group(function () {
    Route::get('/leaderboard', [DiscordBotController::class, 'getLeaderboard']);
    Route::get('/listings/recent', [DiscordBotController::class, 'getRecentListings']);
    Route::get('/users/{username}', [DiscordBotController::class, 'getUserProfile']);
    Route::get('/search', [DiscordBotController::class, 'search']);
    Route::get('/stats', [DiscordBotController::class, 'getStats']);
});

// Discord Integration Routes
Route::prefix('discord')->group(function () {
    // Public verification endpoint (for bot to verify users)
    Route::post('/verify', [DiscordBotController::class, 'verifyDiscord']);

    // Protected endpoints (require user auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/generate-code', [DiscordBotController::class, 'generateVerificationCode']);
        Route::get('/status', [DiscordBotController::class, 'getDiscordStatus']);
    });
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
});

// Stripe routes with rate limiting to prevent abuse
Route::middleware(["auth:sanctum", "throttle:30,1"])->prefix("stripe")->name("stripe.")->group(function () {
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

    // Orders (rate limit order creation to prevent spam)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/seller/all', [OrderController::class, 'sellerOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::middleware('throttle:30,1')->post('/orders', [OrderController::class, 'store']);
    Route::middleware('throttle:30,1')->post('/orders/multi-seller', [OrderController::class, 'createMultiSellerOrders']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{orderId}/items/{itemId}/deliver', [OrderController::class, 'deliverItem']);
    Route::post('/orders/{orderId}/items/{itemId}/confirm-delivery', [OrderController::class, 'confirmDelivery']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Wallet (rate limit financial operations to prevent abuse)
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::middleware('throttle:20,1')->post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::middleware('throttle:10,1')->post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
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
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    Route::get('/messages/{conversationId}', [MessageController::class, 'getMessages']);
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
    Route::post('/messages/send-to-conversation', [MessageController::class, 'sendToConversation']);
    Route::post('/messages/start', [MessageController::class, 'startConversation']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::get('/notifications/recent', [App\Http\Controllers\NotificationController::class, 'recent']);
    Route::get('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'show']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy']);

    // Achievements
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/achievements/category/{category}', [AchievementController::class, 'byCategory']);
    Route::get('/achievements/my', [AchievementController::class, 'userAchievements']);
    Route::get('/achievements/stats', [AchievementController::class, 'userStats']);
    Route::post('/achievements/check', [AchievementController::class, 'checkUnlockable']);
    Route::post('/achievements/{achievementId}/claim', [AchievementController::class, 'claimReward']);
    Route::post('/achievements/claim-all', [AchievementController::class, 'claimAll']);
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

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
    Route::post('/wishlist/check', [WishlistController::class, 'check']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

});

// Image uploads (require authentication with rate limiting)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::post('/upload/images', [UploadController::class, 'uploadMultipleImages']);
    Route::post('/upload/game-logo', [UploadController::class, 'uploadGameLogo']);
    Route::post('/upload/game-icon', [UploadController::class, 'uploadGameIcon']);
    Route::delete('/upload/image', [UploadController::class, 'deleteImage']);
});

// Profile Image Management (Avatar & Banner)
Route::middleware(['auth:sanctum', 'throttle:20,1'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/images', [ProfileController::class, 'getProfileImages'])->name('getImages');
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('uploadAvatar');
    Route::post('/banner', [ProfileController::class, 'uploadBanner'])->name('uploadBanner');
    Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])->name('removeAvatar');
    Route::delete('/banner', [ProfileController::class, 'removeBanner'])->name('removeBanner');
});

// Payment Processor OAuth (Stripe Connect & PayPal)
Route::middleware(['auth:sanctum'])->prefix('payment-processors')->name('payment-processors.')->group(function () {
    // Status check - higher rate limit for development
    Route::get('/status', [\App\Http\Controllers\PaymentProcessorController::class, 'getStatus'])->middleware('throttle:60,1')->name('status');

    // Stripe Connect - stricter rate limit for OAuth flows
    Route::post('/stripe/connect', [\App\Http\Controllers\PaymentProcessorController::class, 'stripeConnect'])->middleware('throttle:10,1')->name('stripe.connect');
    Route::post('/stripe/callback', [\App\Http\Controllers\PaymentProcessorController::class, 'stripeCallback'])->name('stripe.callback')->withoutMiddleware(['auth:sanctum']);
    Route::delete('/stripe/disconnect', [\App\Http\Controllers\PaymentProcessorController::class, 'stripeDisconnect'])->middleware('throttle:10,1')->name('stripe.disconnect');

    // PayPal - stricter rate limit for OAuth flows
    Route::post('/paypal/connect', [\App\Http\Controllers\PaymentProcessorController::class, 'paypalConnect'])->middleware('throttle:10,1')->name('paypal.connect');
    Route::post('/paypal/callback', [\App\Http\Controllers\PaymentProcessorController::class, 'paypalCallback'])->name('paypal.callback')->withoutMiddleware(['auth:sanctum']);
    Route::delete('/paypal/disconnect', [\App\Http\Controllers\PaymentProcessorController::class, 'paypalDisconnect'])->middleware('throttle:10,1')->name('paypal.disconnect');
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

// Admin routes (protected with admin middleware)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
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
    Route::get('/{id}/similar', [ServiceController::class, 'similar'])->name('services.similar');
    Route::post('/', [ServiceController::class, 'store'])->middleware('auth:sanctum')->name('services.store');
    Route::put('/{id}', [ServiceController::class, 'update'])->middleware('auth:sanctum')->name('services.update');
    Route::delete('/{id}', [ServiceController::class, 'destroy'])->middleware('auth:sanctum')->name('services.destroy');
});

Route::group(['prefix' => 'items'], function () {
    Route::get('/', [ItemController::class, 'index'])->name('items.index');
    Route::get('/{id}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/{id}/similar', [ItemController::class, 'similar'])->name('items.similar');
    Route::post('/', [ItemController::class, 'store'])->middleware('auth:sanctum')->name('items.store');
    Route::put('/{id}', [ItemController::class, 'update'])->middleware('auth:sanctum')->name('items.update');
    Route::delete('/{id}', [ItemController::class, 'destroy'])->middleware('auth:sanctum')->name('items.destroy');
});

Route::group(['prefix' => 'currencies'], function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('/{id}', [CurrencyController::class, 'show'])->name('currencies.show');
    Route::get('/{id}/similar', [CurrencyController::class, 'similar'])->name('currencies.similar');
    Route::post('/', [CurrencyController::class, 'store'])->middleware('auth:sanctum')->name('currencies.store');
    Route::put('/{id}', [CurrencyController::class, 'update'])->middleware('auth:sanctum')->name('currencies.update');
    Route::delete('/{id}', [CurrencyController::class, 'destroy'])->middleware('auth:sanctum')->name('currencies.destroy');
});

Route::group(['prefix' => 'accounts'], function () {
    Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/{id}', [AccountController::class, 'show'])->name('accounts.show');
    Route::get('/{id}/similar', [AccountController::class, 'similar'])->name('accounts.similar');
    Route::post('/', [AccountController::class, 'store'])->middleware('auth:sanctum')->name('accounts.store');
    Route::put('/{id}', [AccountController::class, 'update'])->middleware('auth:sanctum')->name('accounts.update');
    Route::delete('/{id}', [AccountController::class, 'destroy'])->middleware('auth:sanctum')->name('accounts.destroy');
});

// Advertisement routes
Route::group(['prefix' => 'advertisements'], function () {
    // Public routes
    Route::get('/active', [AdvertisementController::class, 'getActiveAds'])->name('advertisements.active');
    Route::get('/pricing', [AdvertisementController::class, 'getPricing'])->name('advertisements.pricing');
    Route::post('/{id}/impression', [AdvertisementController::class, 'recordImpression'])->name('advertisements.impression');
    Route::post('/{id}/click', [AdvertisementController::class, 'recordClick'])->name('advertisements.click');

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-ads', [AdvertisementController::class, 'getUserAds'])->name('advertisements.my');
        Route::post('/', [AdvertisementController::class, 'store'])->name('advertisements.store');
        Route::put('/{id}', [AdvertisementController::class, 'update'])->name('advertisements.update');
        Route::delete('/{id}', [AdvertisementController::class, 'destroy'])->name('advertisements.destroy');
    });
});

Route::group(['prefix' => 'providers'], function () {
    Route::get('/', [ProviderController::class, 'index'])->name('providers.index');
    Route::get('/{id}', [ProviderController::class, 'show'])->name('providers.show');
});

Route::prefix('auth')->group(function () {
    Route::get('discord', [AuthController::class, 'redirectToProvider']);
    Route::get('discord/callback', [AuthController::class, 'handleProviderCallback']);
    Route::post('discord/unlink', [AuthController::class, 'unlinkDiscord'])->middleware('auth:sanctum');

    // Rate limit authentication endpoints to prevent brute force attacks
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Email verification (less strict rate limiting)
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    });
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

// PayPal checkout routes (deposits)
Route::middleware(['auth:sanctum', 'throttle:20,1'])->prefix('paypal')->name('paypal.')->group(function () {
    Route::post('/create-order', [PayPalCheckoutController::class, 'createOrder']);
    Route::post('/capture-order', [PayPalCheckoutController::class, 'captureOrder']);
    Route::get('/order/{orderId}', [PayPalCheckoutController::class, 'getOrderDetails']);
});

// PayPal payout routes (withdrawals)
Route::middleware(['auth:sanctum'])->prefix('payouts')->name('payouts.')->group(function () {
    Route::post('/paypal', [PayPalPayoutController::class, 'createPayout']);
    Route::get('/paypal/history', [PayPalPayoutController::class, 'getPayouts']);
    Route::get('/paypal/{payoutBatchId}/status', [PayPalPayoutController::class, 'getPayoutStatus']);
});

// Admin payout management routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/payouts')->name('admin.payouts.')->group(function () {
    Route::get('/pending-reviews', [PayPalPayoutController::class, 'getPendingReviews']);
    Route::post('/{payoutId}/approve', [PayPalPayoutController::class, 'approveManualPayout']);
    Route::post('/{payoutId}/reject', [PayPalPayoutController::class, 'rejectManualPayout']);
});

// PayPal webhooks (no auth required)
Route::post('/paypal/webhook', [PayPalPayoutController::class, 'handleWebhook']);

// Season Pass routes
Route::middleware(['auth:sanctum'])->prefix('season-pass')->group(function () {
    Route::get('/', [App\Http\Controllers\SeasonPassController::class, 'index']);
    Route::post('/purchase', [App\Http\Controllers\SeasonPassController::class, 'purchase']);
    Route::get('/show', [App\Http\Controllers\SeasonPassController::class, 'show']);
});

// Achievement Store routes
Route::middleware(['auth:sanctum'])->prefix('achievement-store')->group(function () {
    Route::get('/', [App\Http\Controllers\AchievementStoreController::class, 'index']);
    Route::post('/{itemId}/purchase', [App\Http\Controllers\AchievementStoreController::class, 'purchase']);
    Route::get('/purchases', [App\Http\Controllers\AchievementStoreController::class, 'purchases']);
    Route::get('/active-perks', [App\Http\Controllers\AchievementStoreController::class, 'activePerks']);
    Route::post('/activate-cosmetic', [App\Http\Controllers\AchievementStoreController::class, 'activateCosmetic']);
    Route::get('/inventory', [App\Http\Controllers\AchievementStoreController::class, 'inventory']);
});

// Public user profile routes (specific routes MUST come before wildcard routes)
Route::get('/users/{userId}/payment-methods', [UserController::class, 'getPaymentMethods'])->where('userId', '[0-9]+');
Route::get('/users/{username}/public', [UserController::class, 'showPublic']);
// Wildcard route MUST be last (catches username or ID)
Route::get('/users/{user}', [UserController::class, 'show']);
