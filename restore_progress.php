<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'seller1@example.com')->first();

echo "Re-adding achievements for: {$user->name}\n\n";

// Unlock first few tiers
$achievementsToUnlock = [
    ['group' => 'total-buyer', 'tier' => 'copper'],
    ['group' => 'total-buyer', 'tier' => 'bronze'],
    ['group' => 'total-buyer', 'tier' => 'silver'],
    ['group' => 'total-buyer', 'tier' => 'gold'],
    ['group' => 'total-seller', 'tier' => 'copper'],
    ['group' => 'total-seller', 'tier' => 'bronze'],
    ['group' => 'social-butterfly', 'tier' => 'copper'],
];

foreach ($achievementsToUnlock as $data) {
    $achievement = App\Models\Achievement::where('achievement_group', $data['group'])
        ->where('tier', $data['tier'])
        ->first();

    if ($achievement && !$achievement->isUnlockedBy($user)) {
        $achievement->unlockFor($user);
        echo "✓ {$achievement->name} (+$" . number_format($achievement->wallet_reward, 2) . ")\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Testing Progress Calculation:\n\n";

// Check Emerald progress
$emerald = App\Models\Achievement::where('achievement_group', 'total-buyer')
    ->where('tier', 'emerald')
    ->first();

if ($emerald) {
    $progress = $emerald->getProgressFor($user);
    echo "Shopping Journey Emerald:\n";
    echo "  Requirements: " . json_encode($emerald->requirements) . "\n";
    echo "  User purchases: " . $user->orders()->where('payment_status', 'completed')->count() . "\n";
    echo "  Progress: " . json_encode($progress) . "\n";
}

$user->refresh();
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Summary:\n";
echo "Achievements unlocked: " . $user->achievements()->count() . "\n";
echo "Wallet balance: $" . number_format($user->wallet_balance, 2) . "\n";
