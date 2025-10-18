# Rewards & Referral System

## Overview
Bootstrap-friendly rewards system that scales transparently with platform growth. Designed to minimize cash outlay while still driving meaningful user acquisition.

## Current Configuration (Bootstrap Phase)

### Referral Bonuses
- **Referrer Reward**: $1.50 platform credit when referred user spends $20+
- **Referred User Welcome**: $1.50 platform credit on first $20+ purchase
- **Type**: Bonus balance (platform credit only, non-withdrawable)
- **Cost per acquisition**: ~$3.00

### Welcome Bonuses (All Users)
| Spend Amount | Bonus Credit |
|-------------|--------------|
| $20-49.99   | $2.00        |
| $50-99.99   | $5.00        |
| $100+       | $10.00       |

### Monthly Referral Rally Event
- **Prize Pool**: $20/month (Bootstrap phase)
- **Prizes**:
  - 🥇 1st Place: $10 platform credit + Top Referrer badge
  - 🥈 2nd Place: $6 platform credit + Top Referrer badge
  - 🥉 3rd Place: $4 platform credit

**Requirements**:
- Referred users must spend $15+ to count
- Email verified accounts only
- Active referrals (2+ purchases) count as 2 points

## Scaling Tiers

As monthly revenue grows, rewards automatically scale:

| Tier | Revenue | Prize Pool | Bonus Multiplier | Message |
|------|---------|-----------|------------------|---------|
| 🌱 Bootstrap | $0-499 | $20/mo | 1.0x | Growing together! |
| 📈 Growth | $500-1,999 | $50/mo | 1.25x | Rewards increasing! |
| 🚀 Scale | $2,000-9,999 | $100/mo | 1.5x | Big rewards! |
| 💎 Established | $10,000+ | $500/mo | 2.0x | Maximum rewards! |

## Free Growth Tactics (Always Active)

- ✅ Top Referrer Badge
- ✅ Featured Seller Spotlight
- ✅ Public Leaderboard Display
- ✅ Social Recognition
- ✅ Custom Titles
- ✅ Profile Highlights

## Configuration

Edit `config/rewards.php` to adjust:
- Bonus amounts
- Minimum purchase requirements
- Prize pools
- Scaling tier thresholds
- Balance type (bonus_balance vs withdrawable balance)

## Why Bonus Balance?

Using `bonus_balance` instead of withdrawable `balance`:
- ✅ 50-70% cost savings (money stays in ecosystem)
- ✅ Users spend it on platform (generating seller fees)
- ✅ Still valuable to active buyers/sellers
- ✅ Can switch to cash rewards as revenue allows

## Implementation Notes

### Backend
- Configuration: `config/rewards.php`
- Models: `Referral`, `ReferralEarning`
- Controller: `ReferralController`

### Frontend
- Banner Component: `components/RewardsScalingBanner.vue`
- Display on: Events page, Referrals page, Dashboard

### Future Enhancements
When revenue allows, consider:
1. Switch to cash bonuses (set `type: 'balance'`)
2. Enable tiered commission system
3. Increase prize pools
4. Add more free growth tactics

## Transparency is Key

Users appreciate honesty. The scaling message sets expectations and gets them invested in platform success:
> "Event prizes and referral bonuses scale with platform growth. As MMO Supply grows, so do the rewards! 🚀"
