# Chargeback Protection System

## Overview

This document describes the chargeback and dispute protection system implemented to protect the marketplace from fraudulent chargebacks and disputes after sellers receive funds.

## Problem Statement

When a buyer purchases a high-value item (e.g., a $500 game account) and the seller receives the payout, there's a risk that the buyer could initiate a chargeback or dispute after receiving the goods. This could result in:
- Platform losing the disputed amount
- Seller keeping both the item and the money
- Buyer receiving both a refund and the item

## Solution: Multi-Layer Protection

### 1. Escrow/Hold System

All seller earnings are held in escrow for a configurable period before they can be withdrawn. The hold period is based on the seller's trust level.

**Hold Periods by Trust Level:**
- **New Sellers** (< 10 sales): 21 days
- **Standard Sellers** (10-50 sales, no issues): 14 days
- **Trusted Sellers** (50+ sales, good history): 7 days
- **Verified Sellers** (manual verification): 3 days

**High-Risk Transactions:**
Transactions over $500 automatically have their hold period multiplied by 1.5x.

### 2. Trust Level System

Sellers are automatically graduated through trust levels based on their performance:

```
NEW → STANDARD → TRUSTED → VERIFIED
```

**Trust Level Criteria:**

| Level | Sales Required | Chargeback Rate | Account Age | Notes |
|-------|---------------|-----------------|-------------|-------|
| New | < 10 | N/A | Any | Default for new accounts |
| Standard | 10+ | < 3% | 2+ months | Automatic upgrade |
| Trusted | 100+ | < 1% | 6+ months | Automatic upgrade |
| Verified | Any | Any | Any | Manual admin verification |

### 3. Chargeback Reserve

A percentage of seller earnings is held as a reserve to cover potential chargebacks:

- **New Sellers**: 20% reserve
- **Standard Sellers**: 10% reserve
- **Trusted Sellers**: 5% reserve
- **Verified Sellers**: 0% reserve

### 4. Risk Scoring Algorithm

Each transaction is assigned a risk score (0-100) based on multiple factors:

**Account Age:**
- < 1 month: +30 points
- 1-3 months: +20 points
- 3+ months: 0 points

**Sales History:**
- < 5 sales: +25 points
- 5-20 sales: +15 points
- 20+ sales: 0 points

**Chargeback History:**
- Chargeback rate × 10 = additional points
- Example: 5% chargeback rate = +50 points

**Transaction Amount:**
- Amounts over $500 trigger high-risk multiplier

### 5. Payout Restrictions

Additional restrictions prevent rapid fund extraction:

- **Daily payout limit**: 3 payouts per day
- **Daily amount limit**: $1,000 total per day
- **Auto-approval threshold**: $500 (above requires manual review)
- **Minimum time between payouts**: 2 hours

## Database Schema

### Transactions Table

```php
// Escrow/hold tracking
is_held: boolean (default: false)
hold_until: timestamp (when funds will be released)
hold_reason: string (why funds are held)

// Chargeback tracking
has_chargeback: boolean (default: false)
chargeback_date: timestamp
chargeback_amount: decimal
chargeback_reason: text
chargeback_status: enum('pending', 'won', 'lost', 'reversed')

// Risk assessment
risk_score: integer (0-100)
risk_factors: json (detailed risk factors)
```

### Users Table

```php
// Seller reputation
seller_reputation_score: integer (cumulative score)
completed_sales: integer (count of successful sales)
disputed_sales: integer (count of disputed transactions)
chargebacks_received: integer (count of chargebacks)

// Trust level
trust_level: enum('new', 'standard', 'trusted', 'verified')

// Payout settings
payout_hold_days: integer (current hold period in days)
chargeback_reserve_percent: decimal (current reserve %)

// Tracking
first_sale_at: timestamp (when they made first sale)
last_chargeback_at: timestamp (most recent chargeback)
```

## Configuration

All settings are configurable via `.env`:

```env
# Hold periods (days)
HOLD_PERIOD_NEW_SELLER=21
HOLD_PERIOD_STANDARD_SELLER=14
HOLD_PERIOD_TRUSTED_SELLER=7
HOLD_PERIOD_VERIFIED_SELLER=3

# Chargeback reserves (%)
RESERVE_NEW_SELLER=20
RESERVE_STANDARD_SELLER=10
RESERVE_TRUSTED_SELLER=5
RESERVE_VERIFIED_SELLER=0

# High-risk settings
HIGH_RISK_AMOUNT_THRESHOLD=500
HIGH_RISK_HOLD_MULTIPLIER=1.5
AUTO_APPROVE_MAX_AMOUNT=100

# Payout restrictions
PAYOUT_DAILY_LIMIT=3
PAYOUT_DAILY_AMOUNT_LIMIT=1000
PAYOUT_AUTO_MAX_AMOUNT=500
PAYOUT_MIN_HOURS_BETWEEN=2
```

## ChargebackProtectionService

The `ChargebackProtectionService` provides all the business logic for the protection system.

### Key Methods

**Calculate Hold Period**
```php
calculateHoldPeriod(User $seller, float $amount): int
```
Returns the number of days funds should be held based on trust level and transaction amount.

**Calculate Risk Score**
```php
calculateRiskScore(User $seller, float $transactionAmount): int
```
Returns a risk score (0-100) based on seller history and transaction details.

**Update Trust Level**
```php
updateTrustLevel(User $seller): void
```
Automatically upgrades seller trust level when they meet criteria.

**Apply Transaction Hold**
```php
applyHold(Transaction $transaction, User $seller): void
```
Applies escrow hold to a completed transaction.

**Check Funds Release**
```php
canReleaseFunds(Transaction $transaction): bool
```
Checks if held funds can be released (hold period expired, no disputes).

## Admin Panel Integration

The Filament admin panel provides tools for managing payouts and disputes:

### PayPal Payouts Resource

**Features:**
- View all payout requests with status filtering
- Badge showing count of pending manual reviews
- Approve/reject actions for high-value payouts
- Detailed view of payout information
- Filters for pending reviews and large amounts

**Actions Available:**
- **Approve**: Processes the payout through PayPal
- **Reject**: Rejects the payout with a reason

## Workflow Example

### Scenario: New seller makes a $500 sale

1. **Order Completed**
   - Buyer pays $500 for game account
   - Seller delivers account
   - Order marked as completed

2. **Funds Held in Escrow**
   - ChargebackProtectionService calculates hold period
   - As new seller with $500 transaction: 21 days × 1.5 = 31 days
   - Transaction marked as `is_held = true`
   - `hold_until` set to 31 days from now
   - Risk score calculated (likely 50-70 for new seller)

3. **Chargeback Reserve Applied**
   - 20% reserve held: $100 reserved
   - Available to withdraw after hold: $400

4. **Seller Attempts Payout**
   - Seller requests $450 payout
   - System checks available funds: $400
   - Payout rejected: "Insufficient available funds (some funds are held in escrow)"

5. **After Hold Period**
   - 31 days pass with no disputes
   - Funds automatically released
   - Seller can now withdraw full $400 (minus reserve still held)

6. **Trust Level Progression**
   - After 10 successful sales: upgraded to "standard" (14 day holds)
   - After 100 successful sales: upgraded to "trusted" (7 day holds)

## Future Enhancements

### Planned Features:

1. **Automatic Fund Release Job**
   - Scheduled task to release funds when `hold_until` date passes
   - Update `is_held = false` automatically

2. **Dispute Resolution UI**
   - Buyer interface to open disputes
   - Admin panel for managing disputes
   - Evidence upload system

3. **Chargeback Webhook Handlers**
   - Stripe webhook for chargeback notifications
   - PayPal webhook for chargeback notifications
   - Automatic deduction from seller wallet when chargeback occurs

4. **Reserve Management**
   - Track reserve balance separately
   - Release reserves after safe period (e.g., 90 days)
   - Admin tools to adjust reserves

5. **Fraud Detection**
   - Pattern detection for suspicious behavior
   - Velocity checks (rapid high-value sales)
   - Device fingerprinting integration
   - IP address reputation checks

6. **Seller Dashboard**
   - View held funds breakdown
   - See projected release dates
   - Track trust level progress
   - Chargeback history

## Testing Recommendations

### Test Cases:

1. **New Seller Low-Value Transaction**
   - $50 sale should have 21 day hold
   - Verify 20% reserve applied

2. **New Seller High-Value Transaction**
   - $600 sale should have 31 day hold (21 × 1.5)
   - Verify risk score is high

3. **Trust Level Progression**
   - Create seller with 9 sales, add 10th
   - Verify automatic upgrade to "standard"

4. **Payout Restrictions**
   - Attempt 4 payouts in one day
   - Verify 4th is rejected

5. **Manual Review Trigger**
   - Request $501 payout
   - Verify `pending_review` status created

6. **Admin Approval Flow**
   - Approve pending review payout
   - Verify PayPal API called
   - Verify status updated to "success"

## Security Considerations

1. **Admin Actions**
   - Approve/reject actions require admin middleware
   - All actions logged for audit trail

2. **API Endpoints**
   - Admin endpoints protected by `auth:sanctum` and `admin` middleware
   - Payout endpoints require user authentication

3. **Rate Limiting**
   - Payout endpoints should have rate limiting
   - Prevent abuse of restriction checks

4. **Data Validation**
   - All amounts validated and sanitized
   - PayPal email verified before payout

## Support and Maintenance

### Monitoring Metrics:

- Average hold period by trust level
- Chargeback rate by trust level
- Percentage of high-risk transactions
- Manual review approval rate
- Average time to fund release

### Regular Reviews:

- Quarterly review of hold periods
- Adjust thresholds based on chargeback data
- Review trust level criteria effectiveness
- Analyze false positive rate (good sellers flagged as high-risk)

## References

- Configuration: `config/escrow.php`
- Service: `app/Services/ChargebackProtectionService.php`
- Migrations: `database/migrations/*chargeback*.php`, `*escrow*.php`
- Admin Panel: `app/Filament/Resources/PayPalPayoutResource.php`
- Controller: `app/Http/Controllers/PayPalPayoutController.php`
