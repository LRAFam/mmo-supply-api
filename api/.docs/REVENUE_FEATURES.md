# Platform Revenue Features

This document outlines the monetization strategies implemented to generate revenue for the platform.

## 1. Platform Transaction Fees (10% Default)

Every order automatically includes a platform fee that is deducted from seller earnings when orders are completed.

### How It Works:
- **Default Fee**: 10% of each sale
- **Reduced Fees**: Available through seller subscriptions (7% Premium, 5% Elite)
- **Calculation**: Automatic on order creation and payment release
- **Database Fields**: `orders.platform_fee_percentage`, `orders.platform_fee`, `orders.seller_earnings`

### Example:
- Buyer pays: $100
- Platform fee (10%): $10
- Seller receives: $90

## 2. Seller Subscription Tiers

Sellers can subscribe to premium tiers for reduced platform fees and additional benefits.

### Tiers:

#### Basic (Free)
- Platform fee: 10%
- Basic seller dashboard
- Email support

#### Premium ($9.99/month)
- **Platform fee: 7%** (Save 3% on every sale!)
- Verified seller badge
- Priority in search results
- Advanced analytics
- Email support

#### Elite ($29.99/month)
- **Platform fee: 5%** (Save 5% on every sale!)
- Elite seller badge
- Top priority in search results
- Advanced analytics & insights
- Priority customer support
- Dedicated account manager

### Revenue Calculation:
- If 100 sellers subscribe to Premium: $999/month
- If 50 sellers subscribe to Elite: $1,499.50/month
- **Combined monthly recurring revenue: $2,498.50**

### API Endpoints:
```
GET  /api/seller-subscriptions/tiers    # Get available tiers (public)
GET  /api/seller-subscriptions/current  # Get user's current subscription
POST /api/seller-subscriptions          # Subscribe to a tier
DELETE /api/seller-subscriptions        # Cancel subscription
```

### Payment Methods:
- Wallet balance (instant activation)
- Stripe (recurring billing - webhook activation)

## 3. Featured Listings

Sellers can pay to boost product visibility with featured placement on homepage and category pages.

### Pricing:
- **7 days**: $5.00
- **14 days**: $8.00
- **30 days**: $15.00

### Benefits:
- Highlighted with special badge
- Top positioning in listings
- Homepage featured section
- Increased visibility and sales

### Revenue Potential:
- If 50 sellers feature products for 14 days monthly: $400/month
- If 20 sellers feature products for 30 days monthly: $300/month
- **Combined monthly revenue: $700**

### API Endpoints:
```
GET  /api/featured-listings/pricing  # Get pricing (public)
GET  /api/featured-listings/active   # Get active featured listings (public)
GET  /api/featured-listings          # Get user's featured listings
POST /api/featured-listings          # Create featured listing
DELETE /api/featured-listings/:id    # Cancel featured listing
```

### Payment Methods:
- Wallet balance (instant activation)
- Stripe (webhook activation)

## 4. Future Revenue Opportunities

### Promotional Campaigns
- Sellers pay to run platform-wide sales/discounts
- Platform promotes these deals site-wide
- Revenue from campaign setup fees ($20-50 per campaign)

### Advertisement Placements
- Banner ads on high-traffic pages
- Sponsored game sections
- Native product recommendations
- CPM/CPC pricing models

### Premium Buyer Features
- Early access to new listings
- Exclusive deals and discounts
- Advanced search/filtering
- Price drop notifications

## Revenue Summary

### Monthly Recurring Revenue (MRR) Potential:
- **Seller Subscriptions**: $2,500+ (with 100 Premium + 50 Elite subscribers)
- **Featured Listings**: $700+ (with 70 active features monthly)
- **Platform Fees**: Variable based on sales volume (10% of all transactions)

### Example with $100,000 monthly sales volume:
- Platform fees: $10,000 (avg 10% fee)
- Subscriptions: $2,500
- Featured listings: $700
- **Total monthly revenue: $13,200**

## Database Schema

### New Tables:

#### `seller_subscriptions`
- Tracks seller subscription tier and status
- Stores fee percentage and pricing
- Handles recurring billing via Stripe

#### `featured_listings`
- Tracks featured product placements
- Duration-based pricing
- Active/inactive status management

#### `orders` (new fields)
- `platform_fee_percentage`: Fee % applied to order
- `platform_fee`: Dollar amount of platform fee
- `seller_earnings`: Net amount seller receives

## Controllers

### SellerSubscriptionController
- Manage subscription tiers
- Handle subscriptions and cancellations
- Process payments via wallet or Stripe

### FeaturedListingController
- Manage featured product placements
- Duration-based pricing
- Product ownership validation

### OrderController (Enhanced)
- Calculate platform fees per seller's subscription
- Release seller earnings after platform fee deduction

## Models

### SellerSubscription
- Relationship to User
- Calculate platform fee based on tier
- Check expiration status
- Get subscription benefits

### FeaturedListing
- Relationship to User and Product
- Check active/valid status
- Duration-based pricing logic

### User (Enhanced)
- Get active subscription
- Calculate platform fee percentage
- Default to basic tier if no subscription

## Integration Notes

### Order Processing Flow:
1. Order created with platform fee calculation
2. Payment processed (wallet or Stripe)
3. Webhook confirms payment
4. On order completion/delivery:
   - Calculate seller's platform fee based on subscription
   - Deduct platform fee from item total
   - Credit seller wallet with net earnings
   - Platform retains fee as revenue

### Subscription Flow:
1. Seller selects tier
2. Payment processed
3. Subscription activated (wallet) or pending (Stripe webhook)
4. Future orders use reduced fee percentage
5. Monthly renewal via Stripe recurring billing

### Featured Listing Flow:
1. Seller selects product and duration
2. Payment processed
3. Product featured in special sections
4. Expires automatically after duration
5. Can be cancelled early by seller

## Next Steps

1. **Frontend Implementation**
   - Seller subscription management page
   - Featured listing management interface
   - Display featured products on homepage
   - Show seller badges based on tier

2. **Stripe Recurring Billing**
   - Set up Stripe subscription products
   - Handle recurring payment webhooks
   - Auto-renewal and cancellation flows

3. **Analytics Dashboard**
   - Track platform revenue metrics
   - Subscription conversion rates
   - Featured listing performance
   - Top earning sellers

4. **Marketing**
   - Promote subscription benefits to sellers
   - Highlight featured listing ROI
   - Create case studies of successful sellers
