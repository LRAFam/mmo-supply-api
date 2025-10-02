# MMO Supply - Subscription Membership Perks

## Overview
MMO Supply offers three membership tiers designed to help sellers grow their business on our marketplace. Each tier provides increasing benefits for marketplace visibility, support, gaming rewards, and earnings.

---

## Free Tier (No Subscription)

### Cost
**$0/month**

### Marketplace Benefits
- ✓ Unlimited basic listings
- ✓ Standard marketplace visibility
- ✓ Basic analytics dashboard

### Support
- ✓ Email support (48-72h response time)

### Gaming & Rewards
- ✓ Free daily spin wheel (24h cooldown)
- ✗ No premium spin access

### Financial
- **You Keep: 70% of every sale** (Platform takes 30%)

### Featured Listings
- Pay-per-use model available

---

## Premium Tier ($9.99/month)

### Cost
**$9.99/month**

### Marketplace Benefits
- ✓ Verified seller badge
- ✓ **3 featured listing slots per month**
- ✓ Priority placement in search results
- ✓ Advanced analytics dashboard
- ✓ Custom storefront colors & branding

### Marketing Tools
- ✓ Email marketing tools
- ✓ Promotional banners

### Support
- ✓ Priority support (24h response time)

### Gaming & Rewards
- ✓ Free daily spin wheel (24h cooldown)
- ✓ **4 premium spin wheel spins per week** (Win up to $250!)
- ✓ Access to exclusive events

### Financial
- **You Keep: 80% of every sale** (Platform takes 20%)
- **+10% increase in earnings vs Free tier**

### Best For
Sellers looking to boost visibility and earn more with moderate marketing needs.

---

## Elite Tier ($29.99/month)

### Cost
**$29.99/month**

### Marketplace Benefits
- ✓ Verified seller badge
- ✓ **Unlimited featured listings**
- ✓ Top priority placement everywhere
- ✓ Premium analytics & insights
- ✓ Full custom storefront
- ✓ White-label options

### Marketing Tools
- ✓ Email marketing tools
- ✓ Promotional banners
- ✓ Advanced marketing suite

### Support
- ✓ Priority support (1-hour response time)
- ✓ **Dedicated account manager**

### Gaming & Rewards
- ✓ Free daily spin wheel (24h cooldown)
- ✓ **8 premium spin wheel spins per week** (Win up to $250!)
- ✓ Access to exclusive events
- ✓ Early feature access

### Advanced Features
- ✓ **API access** for automation
- ✓ Bulk operations tools

### Financial
- **You Keep: 90% of every sale** (Platform takes 10%)
- **+20% increase in earnings vs Free tier**

### Best For
High-volume sellers and power users who want maximum visibility, automation, and earnings.

---

## Feature Comparison Table

| Feature | Free | Premium | Elite |
|---------|------|---------|-------|
| **Marketplace** |
| Unlimited Listings | ✓ | ✓ | ✓ |
| Verified Badge | ✗ | ✓ | ✓ |
| Featured Listings | Pay per use | 3/month | Unlimited |
| Search Priority | Standard | High | Highest |
| Analytics | Basic | Advanced | Premium |
| Custom Storefront | ✗ | ✓ | ✓ + White-label |
| **Marketing** |
| Email Marketing | ✗ | ✓ | ✓ |
| Promotional Banners | ✗ | ✓ | ✓ |
| **Gaming** |
| Free Daily Spin | ✓ | ✓ | ✓ |
| Premium Spins/Week | ✗ | 4 | 8 |
| Exclusive Events | ✗ | ✓ | ✓ |
| Early Features | ✗ | ✗ | ✓ |
| **Support** |
| Response Time | 48-72h | 24h | 1h |
| Account Manager | ✗ | ✗ | ✓ |
| **Advanced** |
| API Access | ✗ | ✗ | ✓ |
| Bulk Operations | ✗ | ✗ | ✓ |
| **Financial** |
| You Keep | 70% | 80% | 90% |

---

## Earnings Comparison Example

### If you make $1,000 in sales per month:

- **Free Tier:** You keep $700 (30% fee = $300)
- **Premium Tier:** You keep $800 (20% fee = $200) — **+$100 more**
  - Premium subscription cost: $9.99
  - **Net gain: +$90.01 per month**

- **Elite Tier:** You keep $900 (10% fee = $100) — **+$200 more**
  - Elite subscription cost: $29.99
  - **Net gain: +$170.01 per month**

### Break-even points:
- **Premium:** Pays for itself at $100/month in sales
- **Elite:** Pays for itself at $300/month in sales

---

## Gaming Features - Premium Spin Wheel

### What is the Premium Spin Wheel?
A subscription-exclusive feature that gives you weekly chances to win wallet credits!

### Premium vs Free Spins
- **Free Spin:** Available to all users once every 24 hours
- **Premium Spins:** Exclusive to Premium (4/week) and Elite (8/week) members

### Prize Potential
- Win up to **$250** in wallet credits per spin
- Use winnings to purchase items, pay fees, or withdraw

### Legal & Safe
Premium spins are a **membership benefit**, not gambling. You're not paying per spin—you're paying for a subscription that includes spins as a perk.

---

## How to Subscribe

1. **Choose Your Plan** - Select Premium ($9.99/month) or Elite ($29.99/month)
2. **Pick Payment Method:**
   - Use Wallet Balance (instant activation)
   - Credit/Debit Card via Stripe
3. **Enjoy Benefits** - All perks activate immediately upon payment

### Cancellation
- Cancel anytime from your subscription page
- No penalties or fees
- Access continues until end of current billing period

---

## FAQ

### Can I upgrade/downgrade my plan?
Yes! You can change your subscription tier at any time. Changes take effect immediately.

### What happens if I cancel?
You lose access to premium features at the end of your current billing period. Free tier access continues.

### Do premium spins expire?
Yes, unused spins reset weekly. Use them or lose them!

### Can I pay with my seller earnings?
Yes! Use your wallet balance to pay for subscriptions directly.

### Is there a free trial?
Not currently, but the Free tier lets you test the platform with no commitment.

---

## Implementation Notes (for Developers)

### Database Structure
- `seller_subscriptions` table stores subscription data
- `users.premium_spins_remaining` tracks weekly spin allocation
- `users.premium_spins_reset_at` handles weekly reset timing

### Key Models
- `SellerSubscription` model handles tier logic and benefits
- `getBenefits()` method returns structured perk data
- `getFormattedBenefits()` returns display-ready perk list

### Spin Allocation Logic
- Premium users: 4 spins/week (resets every 7 days)
- Elite users: 8 spins/week (resets every 7 days)
- Handled in `SpinWheelController::spin()` method
- Weekly reset checked before each spin attempt

### Integration Points
1. **Featured Listings** - Check `featured_listings` benefit
2. **Search Priority** - Check `priority_listings` benefit
3. **Analytics** - Check `analytics` tier (basic/advanced/premium)
4. **Support** - Check `support_response_time` for ticket prioritization
5. **Spin Wheel** - Check `premium_spins_per_week` for allocation

---

**Last Updated:** October 2025
**Version:** 1.0
