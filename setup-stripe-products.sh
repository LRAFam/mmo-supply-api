#!/bin/bash

# Setup Stripe Products and Prices for MMO Supply
# Run this script once to create subscription products in Stripe

echo "🎯 MMO Supply - Stripe Subscription Setup"
echo "=========================================="
echo ""

# Check if Stripe CLI is installed
if ! command -v stripe &> /dev/null
then
    echo "❌ Stripe CLI is not installed."
    echo "📦 Install it from: https://stripe.com/docs/stripe-cli"
    exit 1
fi

echo "✅ Stripe CLI found"
echo ""

# Create Premium Product
echo "📦 Creating Premium Product..."
PREMIUM_PRODUCT=$(stripe products create \
  --name="MMO Supply Premium" \
  --description="Premium membership for MMO marketplace buyers and sellers. Includes 4 premium spins/week, verified badge, priority support, and more!" \
  --format=json)

PREMIUM_PRODUCT_ID=$(echo $PREMIUM_PRODUCT | grep -o '"id": "[^"]*' | head -1 | cut -d'"' -f4)
echo "✅ Premium Product Created: $PREMIUM_PRODUCT_ID"

# Create Premium Price
echo "💰 Creating Premium Price ($9.99/month)..."
PREMIUM_PRICE=$(stripe prices create \
  --product=$PREMIUM_PRODUCT_ID \
  --unit-amount=999 \
  --currency=usd \
  --recurring[interval]=month \
  --format=json)

PREMIUM_PRICE_ID=$(echo $PREMIUM_PRICE | grep -o '"id": "[^"]*' | head -1 | cut -d'"' -f4)
echo "✅ Premium Price Created: $PREMIUM_PRICE_ID"
echo ""

# Create Elite Product
echo "📦 Creating Elite Product..."
ELITE_PRODUCT=$(stripe products create \
  --name="MMO Supply Elite" \
  --description="Elite membership for power users. Includes 8 premium spins/week, elite badge, dedicated account manager, API access, and maximum benefits!" \
  --format=json)

ELITE_PRODUCT_ID=$(echo $ELITE_PRODUCT | grep -o '"id": "[^"]*' | head -1 | cut -d'"' -f4)
echo "✅ Elite Product Created: $ELITE_PRODUCT_ID"

# Create Elite Price
echo "💰 Creating Elite Price ($29.99/month)..."
ELITE_PRICE=$(stripe prices create \
  --product=$ELITE_PRODUCT_ID \
  --unit-amount=2999 \
  --currency=usd \
  --recurring[interval]=month \
  --format=json)

ELITE_PRICE_ID=$(echo $ELITE_PRICE | grep -o '"id": "[^"]*' | head -1 | cut -d'"' -f4)
echo "✅ Elite Price Created: $ELITE_PRICE_ID"
echo ""

# Output .env configuration
echo "================================================"
echo "✅ Setup Complete!"
echo "================================================"
echo ""
echo "Add these to your .env file:"
echo ""
echo "STRIPE_PREMIUM_PRICE_ID=$PREMIUM_PRICE_ID"
echo "STRIPE_ELITE_PRICE_ID=$ELITE_PRICE_ID"
echo ""
echo "================================================"
echo ""
echo "🎉 Your Stripe products are ready!"
echo "Don't forget to also set STRIPE_KEY, STRIPE_SECRET, and STRIPE_WEBHOOK_SECRET in your .env"
