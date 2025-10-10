# Volume-Based Provider Tier System

## Overview

The platform uses an **automatic, performance-based tier system** where providers earn better revenue splits through sales volume, not by paying subscription fees.

## Tier Structure

### 🥉 Standard Provider (80/20 split)
- **Default tier** for all new sellers
- **Requirements**: $0 - $999/month OR $0 - $4,999 lifetime sales
- **Provider Keeps**: 80%
- **Platform Gets**: 20%
- **Badge**: None

### 🥈 Verified Provider (88/12 split)
- **Automatic upgrade** when either threshold is met:
  - $1,000+ in monthly sales, OR
  - $5,000+ in lifetime sales
- **Provider Keeps**: 88%
- **Platform Gets**: 12%
- **Badge**: ✓ Verified
- **Benefits**:
  - Keep 8% more per sale
  - Trust indicator for buyers
  - Priority in search results (coming soon)

### 🥇 Premium Provider (92/8 split)
- **Automatic upgrade** when either threshold is met:
  - $5,000+ in monthly sales, OR
  - $25,000+ in lifetime sales
- **Provider Keeps**: 92%
- **Platform Gets**: 8%
- **Badge**: ⭐ Premium
- **Benefits**:
  - Keep 12% more per sale than Standard
  - Elite trust indicator
  - Top priority in search
  - Featured in provider showcase

## How It Works

### Sales Tracking
1. Every completed order adds to the seller's `monthly_sales` and `lifetime_sales`
2. System automatically checks if thresholds are met
3. Tier is upgraded immediately when threshold is crossed
4. Monthly sales reset on the 1st of each month
5. If monthly sales drop below threshold, lifetime sales can still maintain the tier

### Example Scenarios

**Scenario 1: Fast Riser**
- Month 1: $1,200 in sales → Instant upgrade to Verified (80/20)
- Month 2: $800 in sales → Stays Verified (lifetime is now $2,000)
- Month 3: $5,500 in sales → Instant upgrade to Premium (90/10)

**Scenario 2: Steady Growth**
- Months 1-5: $800/month = $4,000 lifetime → Still Standard
- Month 6: $1,100 in sales = $5,100 lifetime → Upgrade to Verified

**Scenario 3: Seasonal Seller**
- Most months: $500/month → Standard tier
- Holiday month: $6,000 in sales → Upgrade to Premium for that month
- Next month: $500 in sales → Stays Premium (lifetime $25,000+)

## Revenue Comparison

| Monthly Sales | Standard (80%) | Verified (88%) | Premium (92%) |
|---------------|----------------|----------------|---------------|
| $500          | Keep $400      | Keep $440      | Keep $460     |
| $1,000        | Keep $800      | Keep $880      | Keep $920     |
| $5,000        | Keep $4,000    | Keep $4,400    | Keep $4,600   |
| $10,000       | Keep $8,000    | Keep $8,800    | Keep $9,200   |

## Why This Model is Better

### For Providers:
✅ **Fair & Performance-Based**: Earn better rates through success, not payment
✅ **No Upfront Cost**: Don't need to pay to reduce fees
✅ **Transparent**: Clear thresholds, no hidden requirements
✅ **Lifetime Progress**: Build toward tiers permanently
✅ **Dual Pathways**: Monthly OR lifetime sales count

### For Platform:
✅ **Revenue Scales with Volume**: More sales = more platform revenue
✅ **Incentivizes Growth**: Providers work harder to reach next tier
✅ **Fair for All**: Can't pay to skip quality/trust building
✅ **Sustainable**: High-volume sellers still generate significant revenue
✅ **Competitive Advantage**: More attractive than pay-to-win models

## Optional Premium Features (Separate from Tiers)

Subscriptions are now for **marketing features**, not revenue splits:

### Pro Plan ($9.99/mo)
- 3 featured listing slots per month
- Priority placement in search results
- Advanced analytics dashboard
- Custom storefront customization
- Email marketing tools

### Elite Plan ($29.99/mo)
- Everything in Pro
- Unlimited featured listings
- Dedicated account manager
- API access for automation
- Early access to new features
- Premium support (24-hour response)

## API Endpoints

### Get Tier Progress
```bash
GET /api/provider/tier-progress
```

**Response:**
```json
{
  "success": true,
  "progress": {
    "current_tier": "standard",
    "monthly_sales": 450.00,
    "lifetime_sales": 2150.00,
    "earnings_percentage": 80.0,
    "next_tier": "verified",
    "monthly_needed": 550.00,
    "lifetime_needed": 2850.00,
    "monthly_progress": 45.0,
    "lifetime_progress": 43.0
  }
}
```

## Implementation Details

### Database Fields (users table)
- `monthly_sales`: Current month's sales total
- `lifetime_sales`: All-time sales total
- `monthly_sales_reset_at`: Last monthly reset timestamp
- `auto_tier`: Current volume-based tier (standard/verified/premium)
- `creator_tier`: Display tier (can be overridden by admin for partnerships)
- `creator_earnings_percentage`: Admin override for special partnerships

### Automatic Tier Checks
Tiers are automatically recalculated when:
1. A sale is completed (`addSale()` method)
2. Monthly sales are reset (monthly scheduler)
3. Admin manually triggers recalculation

### Monthly Reset Schedule
- Runs on 1st of each month at midnight
- Resets `monthly_sales` to 0
- Rechecks tier eligibility based on lifetime sales
- If lifetime sales maintain tier, provider keeps it

## Admin Overrides

Admins can still set custom earnings for special partnerships:
```php
$user->setCreatorEarnings(95.0, 'partner');
```

This creates a "Partner" tier with custom percentage that overrides the automatic system.

## Marketing Messages

### For Standard Providers:
> "You're keeping 80% of every sale. Reach $1,000/month or $5,000 lifetime to unlock Verified status and keep 88%!"

### For Verified Providers:
> "You're keeping 88% of every sale! Reach $5,000/month or $25,000 lifetime to unlock Premium status and keep 92%!"

### For Premium Providers:
> "You're a Premium Provider keeping 92% of every sale! You've reached the top tier."

## Future Enhancements

1. **Tier Badges**: Visual indicators on profiles and listings
2. **Tier Benefits**: Priority support, early feature access
3. **Tier Leaderboards**: Showcase top providers
4. **Tier Rewards**: Bonus perks at tier milestones
5. **Tier Analytics**: Track time-in-tier, projected upgrades
