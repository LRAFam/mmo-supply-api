# Provider Earnings Model

This platform uses a **provider-first** earnings model where providers keep a percentage of their sales and the platform takes a smaller cut to maintain the marketplace.

## Overview

Instead of charging providers a **platform fee** (like 10-30%), we communicate earnings as:
- **"You keep 70%"** (Standard)
- **"You keep 80%"** (Verified)
- **"You keep 90%"** (Premium)

This is psychologically more positive and provider-friendly than saying "We charge 30%".

## Provider Tiers

### Standard Provider (Free)
- **Keep 70% of every sale** (Platform: 30%)
- Basic provider dashboard
- Email support
- Standard listing visibility
- No badge

### Verified Provider ($9.99/month)
- **Keep 80% of every sale** (Platform: 20%)
- ✓ Verified Badge
- Priority in search results
- Advanced analytics dashboard
- Email support
- **Earn 10% more per sale compared to Standard**

### Premium Provider ($29.99/month)
- **Keep 90% of every sale** (Platform: 10%)
- ⭐ Premium Badge
- Top priority in all listings
- Advanced analytics & insights
- Priority customer support
- Dedicated account manager
- Early access to new features
- **Earn 20% more per sale compared to Standard**

## How It Works

### Order Processing with Provider Earnings:

1. **Buyer purchases** a product for $100
2. System checks **seller's creator earnings percentage**
3. **Calculation**:
   - Standard (70%): Seller gets $70, Platform keeps $30
   - Partner (80%): Seller gets $80, Platform keeps $20
   - Elite (90%): Seller gets $90, Platform keeps $10

4. When order is **completed/delivered**:
   - Seller wallet is credited with their earnings percentage
   - Platform retains the difference as revenue

### Example Calculations:

#### $1,000 in sales as Standard Provider:
- Provider earnings: $700
- Platform revenue: $300

#### $1,000 in sales as Verified Provider:
- Provider earnings: $800
- Platform revenue: $200
- **Monthly subscription cost**: $9.99
- **Net benefit**: +$90.01 compared to Standard

#### $1,000 in sales as Premium Provider:
- Provider earnings: $900
- Platform revenue: $100
- **Monthly subscription cost**: $29.99
- **Net benefit**: +$170.01 compared to Standard

### Break-Even Analysis:

**Verified Tier ($9.99/mo)**
- Earn 10% more per sale
- Break even at: $100 in monthly sales
- Recommended for providers making $100+/month

**Premium Tier ($29.99/mo)**
- Earn 20% more per sale
- Break even at: $150 in monthly sales
- Recommended for providers making $150+/month

## Custom Provider Earnings

For **strategic partnerships**, **high-volume providers**, or **trusted suppliers**, admins can set custom earnings percentages.

### Use Cases:
- **Trusted bulk supplier**: 95% provider earnings (5% platform)
- **Strategic partnership**: 85% earnings with custom tier badge
- **Volume discount**: 92% earnings for providers doing $10K+/month
- **Launch promotion**: Temporary 100% earnings for first 30 days
- **Exclusive distributor**: 93% earnings with premium support

### Admin API Endpoints:
```bash
# Get all providers with custom earnings
GET /api/admin/creators/custom-earnings

# Set custom earnings for a provider
POST /api/admin/creators/{userId}/earnings
{
  "earnings_percentage": 95,
  "tier": "elite",
  "reason": "Exclusive distribution partnership"
}

# Reset to subscription-based earnings
DELETE /api/admin/creators/{userId}/earnings
```

## Database Schema

### Users Table (New Fields):
- `creator_earnings_percentage`: Custom earnings % (overrides subscription)
- `creator_tier`: Visual tier badge (standard, partner, elite)

### How It Works:
1. Check if user has `creator_earnings_percentage` set (custom rate)
2. If yes, use custom rate
3. If no, use subscription tier rate (70%, 80%, 90%)

## API Endpoints

### Public Endpoints:
```bash
# Get available creator tiers
GET /api/seller-subscriptions/tiers
```

**Response:**
```json
[
  {
    "id": "basic",
    "name": "Standard Creator",
    "price": 0,
    "creator_earnings": 70.0,
    "platform_fee": 30.0,
    "features": [
      "Keep 70% of every sale",
      "Basic seller dashboard",
      "Email support"
    ],
    "badge": null
  },
  {
    "id": "premium",
    "name": "Partner Creator",
    "price": 9.99,
    "creator_earnings": 80.0,
    "platform_fee": 20.0,
    "savings": "Earn 10% more per sale!",
    "badge": "🤝 Partner",
    "badge_color": "blue"
  }
]
```

### Creator Endpoints (Authenticated):
```bash
# Get current creator tier and earnings
GET /api/seller-subscriptions/current

# Subscribe to a tier
POST /api/seller-subscriptions
{
  "tier": "premium",
  "payment_method": "wallet"
}

# Cancel subscription
DELETE /api/seller-subscriptions
```

## Revenue Comparison

### Traditional Platform Fee Model:
- "We charge 10% platform fee"
- "Premium: 7% fee"
- "Elite: 5% fee"
- ❌ Feels like platform is taking money

### Creator Earnings Model (This Platform):
- "You keep 70% of your sales"
- "Partner: Keep 80%"
- "Elite: Keep 90%"
- ✅ Feels like creator is earning more

**Same math, better psychology!**

## Benefits of This Model

### For Providers:
1. **Transparent earnings** - Know exactly what you'll make
2. **Incentive to upgrade** - Clear ROI on subscription tiers
3. **Feels empowering** - Focus on what you earn, not what you pay
4. **Scalable** - Higher volume = more value from subscriptions

### For Platform:
1. **Recurring revenue** from subscriptions
2. **Transaction revenue** from all sales
3. **Flexibility** to offer custom rates for partnerships
4. **Competitive positioning** - "Provider-first marketplace"

## Marketing Messages

### Homepage:
- "Keep up to 90% of every sale"
- "Join thousands of providers earning on MMO Supply"
- "No listing fees. No hidden charges. Just fair earnings."

### Provider Dashboard:
- "Your Earnings: 80%" (big, prominent)
- "Upgrade to Premium and keep 90%"
- "This month: You kept $X, buyers paid $Y"

### Tier Comparison:
```
Standard        Verified        Premium
Keep 70%   →    Keep 80%   →    Keep 90%
FREE            $9.99/mo        $29.99/mo
```

## Implementation Notes

### Code Flow:
1. `User->getCreatorEarningsPercentage()` - Gets % creator keeps
2. `User->getPlatformFeePercentage()` - Gets % platform keeps (100 - creator %)
3. Order processing uses creator percentage to calculate:
   - `seller_earnings = total * (creator_percentage / 100)`
   - `platform_fee = total - seller_earnings`

### Priority:
1. Custom `creator_earnings_percentage` (if set)
2. Subscription tier percentage
3. Default: 70% (Standard)

## Future Enhancements

1. **Performance Bonuses**
   - Reach $10K sales: +2% earnings for next month
   - 5-star rating: +1% earnings
   - Top seller of the month: Temporary Elite tier

2. **Referral Program**
   - Invite other creators: Get 1% of their earnings for 6 months
   - Build passive income

3. **Volume Tiers**
   - $0-$1K: 70%
   - $1K-$5K: 75%
   - $5K-$10K: 80%
   - $10K+: 85%

4. **Category-Specific Rates**
   - Digital goods: 90% (low overhead)
   - Physical items: 70% (higher support needs)
   - Services: 80%

## Compliance & Transparency

- All fees clearly displayed before purchase
- Creator dashboard shows exact breakdown
- Transaction history includes platform fee amount
- Annual tax forms show gross sales and net earnings
- Terms of service clearly defines earnings split
