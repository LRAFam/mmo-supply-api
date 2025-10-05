<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Referral;
use App\Models\Order;

echo "🧪 Testing Referral System\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Check if a user has a referral code
echo "Test 1: User Referral Code Generation\n";
$user = User::first();
if ($user) {
    $code = $user->getReferralCode();
    echo "✓ User '{$user->name}' has referral code: {$code}\n";
    echo "✓ Referral link: http://localhost:3000/auth/register?ref={$code}\n";
} else {
    echo "✗ No users found. Please create a user first.\n";
}

echo "\n";

// Test 2: Check referral relationships
echo "Test 2: Referral Relationships\n";
$referralCount = Referral::count();
echo "✓ Total referrals in database: {$referralCount}\n";

if ($referralCount > 0) {
    $referral = Referral::with(['referrer', 'referred'])->first();
    echo "✓ Example: '{$referral->referrer->name}' referred '{$referral->referred->name}'\n";
    echo "✓ Total earnings: \${$referral->total_earnings}\n";
    echo "✓ Total purchases: {$referral->total_purchases}\n";
}

echo "\n";

// Test 3: Check if users have been referred
echo "Test 3: Users With Referrers\n";
$referredUsers = User::whereNotNull('referred_by')->count();
echo "✓ Users referred by others: {$referredUsers}\n";

if ($referredUsers > 0) {
    $referred = User::whereNotNull('referred_by')->with('referrer')->first();
    echo "✓ Example: '{$referred->name}' was referred by '{$referred->referrer->name}'\n";
}

echo "\n";

// Test 4: Leaderboard preview
echo "Test 4: Top Referrers\n";
$topReferrers = User::where('total_referrals', '>', 0)
    ->orderBy('total_referral_earnings', 'desc')
    ->take(3)
    ->get(['name', 'total_referrals', 'total_referral_earnings']);

if ($topReferrers->count() > 0) {
    foreach ($topReferrers as $index => $referrer) {
        $position = $index + 1;
        $medal = $position === 1 ? '🥇' : ($position === 2 ? '🥈' : '🥉');
        echo "{$medal} {$referrer->name}: {$referrer->total_referrals} referrals, \${$referrer->total_referral_earnings} earned\n";
    }
} else {
    echo "✗ No referrers with earnings yet\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Referral system test complete!\n";
