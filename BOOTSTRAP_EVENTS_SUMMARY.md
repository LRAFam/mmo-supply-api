# Bootstrap Events Budget Summary

## Total Monthly Event Budget: ~$80/month

### Active Events Breakdown

| Event | Type | Monthly Cost | Status |
|-------|------|--------------|--------|
| **Monthly Referral Rally** | Tournament | $20 | ✅ NEW - Featured |
| **Discord Boost Rewards** | Giveaway | $15 | ✅ Updated |
| **Weekend Warriors Drop Party** | Drop Party | $15 | ✅ Updated |
| **Flash Giveaway Friday** | Giveaway | $10 (~$40/mo) | ✅ Updated |
| **Review & Win** | Giveaway | $15 | ✅ Updated |
| **Community Treasure Hunt** | Giveaway | $25 | ✅ Updated |
| **Monthly Seller Spotlight** | Tournament | $20 | ✅ Updated |

### Disabled Events (Too Expensive for Bootstrap Phase)

| Event | Original Cost | When Available |
|-------|---------------|----------------|
| Referral Competition | $100 | Replaced by Monthly Referral Rally |
| First Purchase Bonus | $5/user | Replaced by tiered welcome bonus system |
| New Year Extravaganza | $150+ | Growth tier ($500+/mo revenue) |
| PVP Championship | $200+ | Scale tier ($2,000+/mo revenue) |
| Black Friday Bonanza | $600+ | Established tier ($10,000+/mo revenue) |

### Key Changes Made

1. **All prizes now use `bonus_balance`** (platform credit only)
   - Keeps money in ecosystem
   - 50-70% cost savings vs cash prizes
   - Still valuable to active users

2. **Redundant events disabled**
   - Old Referral Competition → New Monthly Referral Rally
   - First Purchase Bonus → Tiered welcome bonus system (config/rewards.php)

3. **Prize pools reduced 70-90%** for active events
   - Discord Boost: $60 → $15
   - Weekend Warriors: $60 → $15
   - Flash Friday: $50 → $10
   - Review & Win: $65 → $15
   - Treasure Hunt: $200 → $25
   - Seller Spotlight: $150 → $20

4. **Transparent scaling message**
   - Users see that prizes grow as platform grows
   - Creates community investment in platform success
   - RewardsScalingBanner shows roadmap

### Estimated Monthly Costs

**Fixed Events:** ~$80/month
- Monthly Referral Rally: $20
- Discord Boost: $15
- Weekend Warriors: $15/weekend = $60/mo
- Flash Friday: $10/week = $40/mo
- Review & Win: $15/mo
- Treasure Hunt: $25/mo (if run monthly)
- Seller Spotlight: $20/mo

**Variable Costs (Welcome Bonuses):**
- New user referral bonuses: $3/qualified referral
- Welcome bonuses: $2-10 per $20+ purchase (first time only)

**Total Bootstrap Phase:** $80-120/month depending on user acquisition

### Growth Scaling Plan

| Tier | Revenue | Event Budget | What Unlocks |
|------|---------|--------------|--------------|
| 🌱 Bootstrap | $0-499 | $80-120/mo | Current active events |
| 📈 Growth | $500-1,999 | $150-250/mo | New Year Extravaganza, 1.25x bonuses |
| 🚀 Scale | $2,000-9,999 | $300-500/mo | PVP Championship, 1.5x bonuses |
| 💎 Established | $10,000+ | $800-1,200/mo | Black Friday Bonanza, 2x bonuses |

### Implementation Status

- ✅ Backend config: `config/rewards.php`
- ✅ Frontend banner: `components/RewardsScalingBanner.vue`
- ✅ Events page integration
- ✅ Referrals page integration
- ✅ Documentation: `REWARDS_SYSTEM.md`
- ⏳ Production database updates: `database/production_complete_update.sql`

### Next Steps

1. Run `production_complete_update.sql` on production server
2. Deploy backend changes (config/rewards.php must exist)
3. Deploy frontend changes (banner component)
4. Monitor user engagement and adjust as needed
5. Plan for tier upgrades as revenue grows
