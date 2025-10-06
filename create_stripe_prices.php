<?php

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET']);

try {
    // Create Premium Product & Price
    echo "Creating Premium product...\n";
    $premiumProduct = \Stripe\Product::create([
        'name' => 'Premium Membership',
        'description' => 'Premium membership with enhanced features and benefits',
    ]);

    echo "Creating Premium price...\n";
    $premiumPrice = \Stripe\Price::create([
        'product' => $premiumProduct->id,
        'unit_amount' => 999, // $9.99
        'currency' => 'usd',
        'recurring' => [
            'interval' => 'month',
        ],
    ]);

    echo "✓ Premium Price ID: {$premiumPrice->id}\n\n";

    // Create Elite Product & Price
    echo "Creating Elite product...\n";
    $eliteProduct = \Stripe\Product::create([
        'name' => 'Elite Membership',
        'description' => 'Elite membership with premium features and dedicated support',
    ]);

    echo "Creating Elite price...\n";
    $elitePrice = \Stripe\Price::create([
        'product' => $eliteProduct->id,
        'unit_amount' => 2999, // $29.99
        'currency' => 'usd',
        'recurring' => [
            'interval' => 'month',
        ],
    ]);

    echo "✓ Elite Price ID: {$elitePrice->id}\n\n";

    echo "========================================\n";
    echo "Add these to your .env file:\n";
    echo "========================================\n";
    echo "STRIPE_PREMIUM_PRICE_ID={$premiumPrice->id}\n";
    echo "STRIPE_ELITE_PRICE_ID={$elitePrice->id}\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
