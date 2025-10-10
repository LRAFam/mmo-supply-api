<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\FeaturedListing;
use App\Models\Event;
use App\Models\Item;
use App\Models\Service;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AIResponseService
{
    /**
     * Generate an AI response to a user's message
     */
    public function generateResponse(User $user, string $message): string
    {
        $messageLower = strtolower(trim($message));

        // Try to match intent and generate response
        $response = $this->matchIntent($user, $messageLower);

        if ($response) {
            return $response;
        }

        // Default helpful response
        return $this->getDefaultResponse($user);
    }

    /**
     * Match user message to an intent and generate response
     */
    private function matchIntent(User $user, string $message): ?string
    {
        // Achievement Points
        if ($this->containsAny($message, ['achievement points', 'how many points', 'point balance', 'my points'])) {
            return $this->getAchievementPointsResponse($user);
        }

        // Order Status
        if ($this->containsAny($message, ['order #', 'order status', 'my order', 'where is my order', 'track order'])) {
            return $this->getOrderStatusResponse($user, $message);
        }

        // Featured Listings
        if ($this->containsAny($message, ['featured listing', 'featured item', 'how long left', 'when expires', 'featured expire'])) {
            return $this->getFeaturedListingsResponse($user);
        }

        // Seller Stats
        if ($this->containsAny($message, ['seller earnings', 'how much earned', 'my sales', 'monthly sales', 'total sales'])) {
            return $this->getSellerStatsResponse($user);
        }

        // Wallet Balance
        if ($this->containsAny($message, ['wallet balance', 'how much money', 'my balance', 'account balance'])) {
            return $this->getWalletBalanceResponse($user);
        }

        // Seller Tier
        if ($this->containsAny($message, ['seller tier', 'what tier', 'tier progress', 'next tier', 'how to rank up'])) {
            return $this->getSellerTierResponse($user);
        }

        // Become Seller
        if ($this->containsAny($message, ['become seller', 'how to sell', 'start selling', 'create listing'])) {
            return $this->getBecomeSellerResponse($user);
        }

        // Platform Fees
        if ($this->containsAny($message, ['platform fee', 'commission', 'how much do you take', 'seller fee', 'earnings percentage'])) {
            return $this->getPlatformFeeResponse($user);
        }

        // Payments
        if ($this->containsAny($message, ['payment method', 'how to pay', 'stripe', 'wallet payment', 'accepted payment'])) {
            return $this->getPaymentMethodsResponse();
        }

        // Referrals
        if ($this->containsAny($message, ['referral', 'refer friend', 'referral code', 'invite'])) {
            return $this->getReferralResponse($user);
        }

        // Achievements
        if ($this->containsAny($message, ['achievement', 'unlock', 'how to get', 'achievement list'])) {
            return $this->getAchievementsResponse($user);
        }

        // Support/Help
        if ($this->containsAny($message, ['help', 'support', 'contact', 'problem', 'issue', 'stuck'])) {
            return $this->getHelpResponse();
        }

        // Greetings
        if ($this->containsAny($message, ['hi', 'hello', 'hey', 'greetings', 'good morning', 'good afternoon'])) {
            return $this->getGreetingResponse($user);
        }

        // Thanks
        if ($this->containsAny($message, ['thank', 'thanks', 'thx', 'appreciate'])) {
            return "You're very welcome! I'm always here if you need anything else. 😊";
        }

        // Events
        if ($this->containsAny($message, ['event', 'tournament', 'competition', 'what\'s happening', 'active events'])) {
            return $this->getEventsResponse();
        }

        // Spin Wheel
        if ($this->containsAny($message, ['spin wheel', 'can i spin', 'spin available', 'free spin'])) {
            return $this->getSpinWheelResponse($user);
        }

        // Subscription Benefits
        if ($this->containsAny($message, ['premium', 'elite', 'subscription', 'what do i get', 'membership benefits'])) {
            return $this->getSubscriptionResponse($user);
        }

        // Withdrawal
        if ($this->containsAny($message, ['withdraw', 'cash out', 'payout', 'transfer money', 'can i withdraw'])) {
            return $this->getWithdrawalResponse($user);
        }

        // Popular/Trending
        if ($this->containsAny($message, ['popular', 'trending', 'hot', 'best selling', 'what\'s popular'])) {
            return $this->getPopularProductsResponse();
        }

        // Recent Activity
        if ($this->containsAny($message, ['recent activity', 'what have i done', 'my activity', 'history'])) {
            return $this->getRecentActivityResponse($user);
        }

        // Tips/Recommendations
        if ($this->containsAny($message, ['tips', 'advice', 'recommendations', 'help me improve', 'what should i do'])) {
            return $this->getPersonalizedTipsResponse($user);
        }

        return null;
    }

    /**
     * Get achievement points response
     */
    private function getAchievementPointsResponse(User $user): string
    {
        $points = $user->achievement_points ?? 0;
        $achievementsCount = $user->achievements()->count();

        $response = "You currently have **{$points} achievement points**! 🏆\n\n";
        $response .= "You've unlocked **{$achievementsCount} achievements** so far.\n\n";

        if ($points >= 1000) {
            $response .= "Wow! That's an impressive collection! You can spend these points in the Achievement Store.";
        } elseif ($points >= 500) {
            $response .= "Great progress! Keep unlocking achievements to earn more points.";
        } else {
            $response .= "Keep playing to unlock more achievements and earn points!";
        }

        return $response;
    }

    /**
     * Get order status response
     */
    private function getOrderStatusResponse(User $user, string $message): string
    {
        // Try to extract order ID from message
        preg_match('/#?(\d+)/', $message, $matches);

        if (isset($matches[1])) {
            $orderId = $matches[1];
            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if ($order) {
                $statusEmoji = match($order->status) {
                    'pending' => '⏳',
                    'processing' => '🔄',
                    'delivered' => '✅',
                    'completed' => '🎉',
                    'cancelled' => '❌',
                    default => '📦'
                };

                return "Order #**{$order->id}** {$statusEmoji}\n\n" .
                       "**Status:** " . ucfirst($order->status) . "\n" .
                       "**Total:** \${$order->total}\n" .
                       "**Payment:** " . ucfirst($order->payment_status) . "\n\n" .
                       "You can view full details at: /orders/{$order->id}";
            }

            return "I couldn't find order #{$orderId} in your account. Please check the order number and try again!";
        }

        // Show recent orders
        $recentOrders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        if ($recentOrders->isEmpty()) {
            return "You don't have any orders yet. Browse our marketplace to find great deals!";
        }

        $response = "Here are your recent orders:\n\n";
        foreach ($recentOrders as $order) {
            $statusEmoji = match($order->status) {
                'pending' => '⏳',
                'processing' => '🔄',
                'delivered' => '✅',
                'completed' => '🎉',
                'cancelled' => '❌',
                default => '📦'
            };

            $response .= "• Order #**{$order->id}** - {$statusEmoji} " . ucfirst($order->status) . " - \${$order->total}\n";
        }

        $response .= "\nTo check a specific order, just ask me about it like: \"What's the status of order #123?\"";

        return $response;
    }

    /**
     * Get featured listings response
     */
    private function getFeaturedListingsResponse(User $user): string
    {
        if (!$user->is_seller) {
            return "You'll need to be a seller to have featured listings! Would you like to know how to become a seller?";
        }

        $featuredListings = FeaturedListing::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->get();

        if ($featuredListings->isEmpty()) {
            return "You don't have any active featured listings right now.\n\n" .
                   "Featured listings get **3x more visibility** and appear at the top of search results! " .
                   "You can feature any of your listings from your seller dashboard.";
        }

        $response = "Your active featured listings:\n\n";

        foreach ($featuredListings as $listing) {
            $expiresAt = \Carbon\Carbon::parse($listing->expires_at);
            $now = now();

            // Calculate time remaining in a human-friendly way
            $totalHours = (int) $now->diffInHours($expiresAt, false);
            $daysLeft = (int) floor($totalHours / 24);
            $hoursLeft = $totalHours % 24;

            if ($daysLeft > 0 && $hoursLeft > 0) {
                $dayLabel = $daysLeft === 1 ? 'day' : 'days';
                $hourLabel = $hoursLeft === 1 ? 'hour' : 'hours';
                $timeLeft = "{$daysLeft} {$dayLabel}, {$hoursLeft} {$hourLabel}";
            } elseif ($daysLeft > 0) {
                $dayLabel = $daysLeft === 1 ? 'day' : 'days';
                $timeLeft = "{$daysLeft} {$dayLabel}";
            } elseif ($hoursLeft > 0) {
                $hourLabel = $hoursLeft === 1 ? 'hour' : 'hours';
                $timeLeft = "{$hoursLeft} {$hourLabel}";
            } else {
                $minutesLeft = (int) $now->diffInMinutes($expiresAt, false);
                if ($minutesLeft > 0) {
                    $minuteLabel = $minutesLeft === 1 ? 'minute' : 'minutes';
                    $timeLeft = "{$minutesLeft} {$minuteLabel}";
                } else {
                    $timeLeft = "less than a minute";
                }
            }

            // Get listing title or product name
            $listingTitle = $listing->listing_title ?? $listing->product_name ?? 'Featured Item';

            $response .= "• **{$listingTitle}** - ⏰ {$timeLeft} remaining\n";
        }

        $response .= "\nYou'll get a notification when a featured listing is about to expire!";

        return $response;
    }

    /**
     * Get seller stats response
     */
    private function getSellerStatsResponse(User $user): string
    {
        if (!$user->is_seller) {
            return "You're not a seller yet. Would you like to know how to start selling on MMO Supply?";
        }

        $monthlySales = $user->monthly_sales ?? 0;
        $lifetimeSales = $user->lifetime_sales ?? 0;
        $wallet = $user->wallet;
        $walletBalance = $wallet ? floatval($wallet->balance) : 0;

        $response = "📊 **Your Seller Stats**\n\n";
        $response .= "💰 **This Month:** \$" . number_format($monthlySales, 2) . "\n";
        $response .= "🎯 **Lifetime Sales:** \$" . number_format($lifetimeSales, 2) . "\n";
        $response .= "💳 **Wallet Balance:** \$" . number_format($walletBalance, 2) . "\n\n";

        $earningsPercentage = $user->getSellerEarningsPercentage();
        $response .= "You currently earn **{$earningsPercentage}%** of each sale after platform fees.";

        return $response;
    }

    /**
     * Get wallet balance response
     */
    private function getWalletBalanceResponse(User $user): string
    {
        // Use wallet relationship for actual balance
        $wallet = $user->wallet;
        $walletBalance = $wallet ? floatval($wallet->balance) : 0;
        $bonusBalance = $user->bonus_balance ?? 0;
        $totalBalance = $walletBalance + $bonusBalance;

        $response = "💰 **Your Wallet Balance**\n\n";
        $response .= "**Available:** \$" . number_format($walletBalance, 2) . "\n";

        if ($bonusBalance > 0) {
            $response .= "**Bonus:** \$" . number_format($bonusBalance, 2) . "\n";
            $response .= "**Total:** \$" . number_format($totalBalance, 2) . "\n\n";
            $response .= "ℹ️ Bonus balance can only be used for purchases, not withdrawals.";
        } else {
            $response .= "\nYou can use your wallet balance to make purchases or withdraw to your bank account!";
        }

        return $response;
    }

    /**
     * Get seller tier response
     */
    private function getSellerTierResponse(User $user): string
    {
        if (!$user->is_seller) {
            return "You're not a seller yet. Become a seller to access our tier system and earn higher percentages!";
        }

        $tierProgress = $user->getTierProgress();
        $currentTier = $tierProgress['current_tier'];
        $earningsPercentage = $tierProgress['earnings_percentage'];

        $response = "🎖️ **Your Seller Tier: " . ucfirst($currentTier) . "**\n\n";
        $response .= "You earn **{$earningsPercentage}%** of each sale.\n\n";

        if ($tierProgress['next_tier']) {
            $nextTier = ucfirst($tierProgress['next_tier']);
            $monthlyNeeded = $tierProgress['monthly_needed'];
            $lifetimeNeeded = $tierProgress['lifetime_needed'];

            $response .= "**Next Tier:** {$nextTier}\n";
            $response .= "**Progress:**\n";
            $response .= "• \${$monthlyNeeded} more in monthly sales, OR\n";
            $response .= "• \${$lifetimeNeeded} more in lifetime sales\n\n";

            $response .= "**Tier Benefits:**\n";
            $response .= "• **Standard (80%):** New sellers\n";
            $response .= "• **Verified (88%):** \$1,000/month or \$5,000 lifetime\n";
            $response .= "• **Premium (92%):** \$5,000/month or \$25,000 lifetime";
        } else {
            $response .= "🏆 You've reached the **highest tier!** Amazing work!";
        }

        return $response;
    }

    /**
     * Get become seller response
     */
    private function getBecomeSellerResponse(User $user): string
    {
        if ($user->is_seller) {
            return "You're already a seller! 🎉\n\nYou can create new listings from your seller dashboard at /seller/listings";
        }

        return "**Want to become a seller?** Here's how:\n\n" .
               "1. Go to your dashboard\n" .
               "2. Click \"Become a Seller\"\n" .
               "3. Create your first listing\n\n" .
               "**Seller Benefits:**\n" .
               "• Earn 80-92% of each sale\n" .
               "• Access to featured listings\n" .
               "• Automated tier progression\n" .
               "• Instant payouts for verified sellers\n\n" .
               "Ready to start? Visit /become-seller";
    }

    /**
     * Get platform fee response
     */
    private function getPlatformFeeResponse(User $user): string
    {
        if ($user->is_seller) {
            $earningsPercentage = $user->getSellerEarningsPercentage();
            $platformFee = 100 - $earningsPercentage;

            return "As a **" . ucfirst($user->auto_tier ?? 'standard') . "** seller, you earn **{$earningsPercentage}%** of each sale.\n\n" .
                   "Platform fee: **{$platformFee}%**\n\n" .
                   "The more you sell, the higher your tier and earnings percentage! 📈";
        }

        return "**MMO Supply Seller Earnings:**\n\n" .
               "• **Standard:** 80% (New sellers)\n" .
               "• **Verified:** 88% (\$1k/month or \$5k lifetime)\n" .
               "• **Premium:** 92% (\$5k/month or \$25k lifetime)\n\n" .
               "Tier progression is automatic based on your sales volume!";
    }

    /**
     * Get payment methods response
     */
    private function getPaymentMethodsResponse(): string
    {
        return "**We accept the following payment methods:**\n\n" .
               "💳 **Stripe** - Credit/Debit cards\n" .
               "💰 **Wallet** - Use your MMO Supply wallet balance\n" .
               "🪙 **Crypto** - Bitcoin, Ethereum, and more\n\n" .
               "Your wallet balance can be funded via:\n" .
               "• Seller earnings\n" .
               "• Direct deposits\n" .
               "• Bonus rewards from achievements\n\n" .
               "All payments are secure and processed instantly!";
    }

    /**
     * Get referral response
     */
    private function getReferralResponse(User $user): string
    {
        $referralCode = $user->getReferralCode();
        $totalReferrals = $user->total_referrals ?? 0;
        $totalEarnings = $user->total_referral_earnings ?? 0;

        $response = "**Your Referral Program Stats:**\n\n";
        $response .= "🎟️ **Your Code:** `{$referralCode}`\n";
        $response .= "👥 **Total Referrals:** {$totalReferrals}\n";
        $response .= "💰 **Total Earnings:** \${$totalEarnings}\n\n";

        $response .= "**How it works:**\n";
        $response .= "• Share your code with friends\n";
        $response .= "• They get a bonus when signing up\n";
        $response .= "• You earn 5% of their purchases forever!\n\n";

        $response .= "Share your code at: /referrals";

        return $response;
    }

    /**
     * Get achievements response
     */
    private function getAchievementsResponse(User $user): string
    {
        $totalUnlocked = $user->achievements()->count();
        $totalAchievements = DB::table('achievements')->where('is_active', true)->count();
        $completionPercentage = $totalAchievements > 0
            ? round(($totalUnlocked / $totalAchievements) * 100)
            : 0;

        $response = "🏆 **Your Achievements**\n\n";
        $response .= "Unlocked: **{$totalUnlocked}/{$totalAchievements}** ({$completionPercentage}%)\n";
        $response .= "Points: **" . ($user->achievement_points ?? 0) . "**\n\n";

        $response .= "**Achievement Categories:**\n";
        $response .= "• 🛍️ Buyer - Make purchases\n";
        $response .= "• 💼 Seller - Complete sales\n";
        $response .= "• 👥 Social - Refer friends, reviews\n";
        $response .= "• ⭐ Special - Secret achievements\n\n";

        $response .= "View all achievements at: /achievements";

        return $response;
    }

    /**
     * Get help/support response
     */
    private function getHelpResponse(): string
    {
        return "**I'm here to help!** 🤝\n\n" .
               "**I can answer questions about:**\n" .
               "• Your orders and their status\n" .
               "• Wallet balance and transactions\n" .
               "• Seller stats and tier progress\n" .
               "• Achievement points and unlocks\n" .
               "• Featured listings and timing\n" .
               "• Platform fees and earnings\n" .
               "• Referral program\n\n" .
               "**Need human support?**\n" .
               "Contact our team at: /contact\n\n" .
               "Just ask me anything - I'm here 24/7! 😊";
    }

    /**
     * Get greeting response
     */
    private function getGreetingResponse(User $user): string
    {
        $greetings = [
            "Hi {$user->name}! 👋 How can I help you today?",
            "Hello {$user->name}! 😊 What can I do for you?",
            "Hey there {$user->name}! 🎮 Need help with anything?",
            "Welcome back {$user->name}! ⚡ What would you like to know?",
        ];

        return $greetings[array_rand($greetings)];
    }

    /**
     * Get default response when no intent matches
     */
    private function getDefaultResponse(User $user): string
    {
        return "I'm not quite sure how to help with that, but I'm learning! 🤖\n\n" .
               "**Here are some things I can help with:**\n" .
               "• Check your order status\n" .
               "• View your wallet balance\n" .
               "• See your achievement points\n" .
               "• Track featured listings\n" .
               "• Explain seller tiers\n" .
               "• Answer questions about payments\n\n" .
               "Try asking me something like:\n" .
               "• \"What's my wallet balance?\"\n" .
               "• \"How many achievement points do I have?\"\n" .
               "• \"What's my seller tier?\"";
    }

    /**
     * Get active events response
     */
    private function getEventsResponse(): string
    {
        $activeEvents = Event::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('end_date', 'asc')
            ->limit(3)
            ->get();

        if ($activeEvents->isEmpty()) {
            return "There are no active events right now. 📅\n\n" .
                   "Check back soon - we regularly host tournaments, competitions, and special events with amazing prizes!\n\n" .
                   "Visit /events to see upcoming events.";
        }

        $response = "🎮 **Active Events:**\n\n";

        foreach ($activeEvents as $event) {
            $endsAt = Carbon::parse($event->end_date);
            $daysLeft = now()->diffInDays($endsAt);
            $timeLeft = $daysLeft > 0 ? "{$daysLeft} days left" : "Ends today!";

            $prizePool = $event->prize_pool ? "\${$event->prize_pool}" : "TBA";

            $response .= "**{$event->name}**\n";
            $response .= "⏰ {$timeLeft}\n";
            $response .= "💰 Prize Pool: {$prizePool}\n\n";
        }

        $response .= "Join an event at: /events";

        return $response;
    }

    /**
     * Get spin wheel response
     */
    private function getSpinWheelResponse(User $user): string
    {
        // Check last spin from database
        $lastSpin = DB::table('spin_wheel_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastSpin) {
            return "🎡 **You have a FREE spin available!**\n\n" .
                   "Spin the wheel to win amazing prizes:\n" .
                   "• Wallet bonuses\n" .
                   "• Achievement points\n" .
                   "• Discount coupons\n" .
                   "• And more!\n\n" .
                   "Spin now at: /spin";
        }

        $lastSpinTime = Carbon::parse($lastSpin->created_at);
        $nextSpinTime = $lastSpinTime->addHours(24);
        $now = now();

        if ($now->gte($nextSpinTime)) {
            return "🎡 **Your FREE spin is ready!**\n\n" .
                   "Spin the wheel for a chance to win prizes!\n\n" .
                   "Spin now at: /spin";
        }

        $hoursLeft = $now->diffInHours($nextSpinTime);
        $minutesLeft = $now->copy()->addHours($hoursLeft)->diffInMinutes($nextSpinTime);

        return "🎡 **Spin Wheel Status**\n\n" .
               "Your next FREE spin will be available in:\n" .
               "⏰ **{$hoursLeft}h {$minutesLeft}m**\n\n" .
               "Come back then to win prizes!";
    }

    /**
     * Get subscription benefits response
     */
    private function getSubscriptionResponse(User $user): string
    {
        $currentTier = $user->subscription_tier ?? 'free';

        $response = "💎 **MMO Supply Membership Tiers**\n\n";

        if ($currentTier === 'free') {
            $response .= "You're currently on the **Free** tier.\n\n";
        } elseif ($currentTier === 'premium') {
            $response .= "You're a **Premium** member! ⭐\n\n";
        } elseif ($currentTier === 'elite') {
            $response .= "You're an **Elite** member! 👑\n\n";
        }

        $response .= "**🆓 Free Tier:**\n";
        $response .= "• Access to marketplace\n";
        $response .= "• Basic achievements\n";
        $response .= "• Daily spin wheel\n\n";

        $response .= "**⭐ Premium Tier ($9.99/mo):**\n";
        $response .= "• Lower marketplace fees\n";
        $response .= "• Priority support\n";
        $response .= "• Exclusive achievements\n";
        $response .= "• 2x daily spins\n";
        $response .= "• Seller boost perks\n\n";

        $response .= "**👑 Elite Tier ($24.99/mo):**\n";
        $response .= "• Lowest marketplace fees\n";
        $response .= "• 24/7 VIP support\n";
        $response .= "• All Premium benefits\n";
        $response .= "• 5x daily spins\n";
        $response .= "• Featured listing discounts\n";
        $response .= "• Custom profile themes\n\n";

        if ($currentTier === 'free') {
            $response .= "Upgrade at: /premium";
        } else {
            $response .= "Manage subscription at: /subscriptions";
        }

        return $response;
    }

    /**
     * Get withdrawal eligibility response
     */
    private function getWithdrawalResponse(User $user): string
    {
        $eligibility = $user->getWithdrawalEligibility();
        $wallet = $user->wallet;
        $walletBalance = $wallet ? floatval($wallet->balance) : 0;

        if ($eligibility['can_withdraw']) {
            return "💰 **You can withdraw funds!**\n\n" .
                   "**Available Balance:** \$" . number_format($walletBalance, 2) . "\n" .
                   "**Minimum Withdrawal:** \$10.00\n\n" .
                   "Withdraw to:\n" .
                   "• Bank account (via Stripe)\n" .
                   "• Crypto wallet\n\n" .
                   "Process withdrawal at: /wallet";
        }

        $response = "⚠️ **Withdrawal Requirements:**\n\n";

        if (!$eligibility['checks']['email_verified']) {
            $response .= "❌ Email not verified - Please verify your email\n";
        } else {
            $response .= "✅ Email verified\n";
        }

        if (!$eligibility['checks']['has_purchases']) {
            $response .= "❌ No purchases made - Make at least one purchase\n";
        } else {
            $response .= "✅ Purchases made\n";
        }

        if (!$eligibility['checks']['withdrawals_enabled']) {
            $response .= "❌ Withdrawals not enabled - Contact support\n";
        } else {
            $response .= "✅ Withdrawals enabled\n";
        }

        if (!$eligibility['checks']['cooldown_passed']) {
            $withdrawalDate = Carbon::parse($eligibility['withdrawal_eligible_at']);
            $daysLeft = now()->diffInDays($withdrawalDate);
            $response .= "❌ Cooldown active - Wait {$daysLeft} more days\n";
        }

        $response .= "\n**Current Balance:** \$" . number_format($walletBalance, 2);

        return $response;
    }

    /**
     * Get popular products response
     */
    private function getPopularProductsResponse(): string
    {
        // Get top selling items from the past week
        $popularItems = DB::table('order_items')
            ->select('product_name', 'product_type', DB::raw('COUNT(*) as sales_count'))
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('product_name', 'product_type')
            ->orderBy('sales_count', 'desc')
            ->limit(5)
            ->get();

        if ($popularItems->isEmpty()) {
            return "🔥 **Trending This Week:**\n\n" .
                   "Browse our marketplace to discover great deals!\n\n" .
                   "Categories:\n" .
                   "• 🎮 Game Currency\n" .
                   "• ⚔️ Items & Equipment\n" .
                   "• 👤 Accounts\n" .
                   "• 🎯 Boosting Services\n\n" .
                   "Explore at: /marketplace";
        }

        $response = "🔥 **Trending This Week:**\n\n";

        foreach ($popularItems as $item) {
            $emoji = match($item->product_type) {
                'currency', 'currencies' => '💰',
                'item', 'items' => '⚔️',
                'account', 'accounts' => '👤',
                'service', 'services' => '🎯',
                default => '📦'
            };

            $response .= "{$emoji} **{$item->product_name}** - {$item->sales_count} sales\n";
        }

        $response .= "\nBrowse more at: /marketplace";

        return $response;
    }

    /**
     * Get recent activity response
     */
    private function getRecentActivityResponse(User $user): string
    {
        $recentOrders = Order::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentAchievements = DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->where('unlocked_at', '>=', now()->subDays(7))
            ->count();

        $recentMessages = DB::table('messages')
            ->where('sender_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $response = "📊 **Your Recent Activity (Last 7 Days)**\n\n";
        $response .= "🛍️ **Orders:** {$recentOrders}\n";
        $response .= "🏆 **Achievements Unlocked:** {$recentAchievements}\n";
        $response .= "💬 **Messages Sent:** {$recentMessages}\n\n";

        if ($user->is_seller) {
            $recentSales = DB::table('order_items')
                ->where('seller_id', $user->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $response .= "💼 **Sales Made:** {$recentSales}\n\n";
        }

        if ($recentOrders + $recentAchievements + $recentMessages === 0) {
            $response .= "You've been quiet lately! Why not:\n";
            $response .= "• Browse the marketplace\n";
            $response .= "• Try the spin wheel\n";
            $response .= "• Check out active events";
        } else {
            $response .= "Keep up the great activity! 🎉";
        }

        return $response;
    }

    /**
     * Get personalized tips response
     */
    private function getPersonalizedTipsResponse(User $user): string
    {
        $tips = [];
        $achievementPoints = $user->achievement_points ?? 0;

        // Wallet tip
        $wallet = $user->wallet;
        if ($wallet && $wallet->balance > 50 && !$user->is_seller) {
            $formattedBalance = number_format($wallet->balance, 2);
            $tips[] = "💡 You have \${$formattedBalance} in your wallet! Consider making a purchase or withdrawing funds.";
        }

        // Achievement points tip
        if ($achievementPoints >= 500) {
            $tips[] = "🏆 You have {$achievementPoints} achievement points! Visit the Achievement Store to spend them on exclusive items.";
        }

        // Seller tier tip
        if ($user->is_seller) {
            $tierProgress = $user->getTierProgress();
            if ($tierProgress['next_tier']) {
                $monthlyNeeded = $tierProgress['monthly_needed'];
                if ($monthlyNeeded < 500) {
                    $tips[] = "📈 You're only \${$monthlyNeeded} away from {$tierProgress['next_tier']} tier! Keep selling to unlock higher earnings.";
                }
            }
        }

        // Not a seller tip
        if (!$user->is_seller) {
            $tips[] = "💼 Did you know? You can become a seller and earn 80-92% of each sale. Start at: /become-seller";
        }

        // Featured listing tip
        if ($user->is_seller) {
            $activeListings = FeaturedListing::where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->count();

            if ($activeListings === 0) {
                $tips[] = "⭐ Featured listings get 3x more visibility! Consider featuring your top product.";
            }
        }

        // Referral tip
        $totalReferrals = $user->total_referrals ?? 0;
        if ($totalReferrals === 0) {
            $tips[] = "🎟️ Share your referral code to earn 5% of your friends' purchases forever!";
        }

        // Daily spin tip
        $lastSpin = DB::table('spin_wheel_history')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if (!$lastSpin) {
            $tips[] = "🎡 Don't forget your free daily spin! Win prizes at: /spin";
        }

        // Return tips or default response
        if (empty($tips)) {
            return "💡 **You're doing great!**\n\n" .
                   "Here are some ways to maximize your experience:\n" .
                   "• Complete achievements for points\n" .
                   "• Refer friends for passive income\n" .
                   "• Join active events for prizes\n" .
                   "• Spin the wheel daily\n" .
                   "• Leave reviews to help the community";
        }

        $response = "💡 **Personalized Tips for You:**\n\n";
        foreach ($tips as $index => $tip) {
            $response .= ($index + 1) . ". " . $tip . "\n\n";
        }

        return rtrim($response);
    }

    /**
     * Check if message contains any of the given keywords
     */
    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
