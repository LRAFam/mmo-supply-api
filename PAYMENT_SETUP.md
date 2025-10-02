# Payment System Setup Guide

## Overview

This guide will help you set up Stripe, Stripe Connect, and cryptocurrency payments for your MMO Supply marketplace.

---

## Phase 1: Stripe Setup (Deposits & Basic Payments)

### 1.1 Create Stripe Account
1. Go to https://stripe.com and create an account
2. Complete business verification
3. Navigate to **Dashboard** → **Developers** → **API keys**

### 1.2 Configure Environment Variables

Add to `/api/.env`:

```env
# Stripe Configuration
STRIPE_KEY=pk_test_xxxxxxxxxxxxx           # Your publishable key
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx        # Your secret key
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx  # Webhook signing secret (see step 1.3)
```

### 1.3 Set Up Webhooks

1. Go to **Stripe Dashboard** → **Developers** → **Webhooks**
2. Click **Add endpoint**
3. **Endpoint URL**: `https://your-domain.com/api/stripe/webhook`
4. **Select events to listen to**:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `account.updated` (for Connect)
   - `transfer.created` (for Connect)
   - `payout.paid` (for Connect)
   - `payout.failed` (for Connect)
5. Copy the **Signing secret** and add it to `.env` as `STRIPE_WEBHOOK_SECRET`

### 1.4 Test Webhook Locally (Development)

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/stripe/webhook

# Copy the webhook signing secret to .env
```

### 1.5 Test Card Numbers

Use these in development:
- **Success**: `4242 4242 4242 4242`
- **Decline**: `4000 0000 0000 0002`
- **3D Secure**: `4000 0025 0000 3155`
- **Expiry**: Any future date (e.g., 12/34)
- **CVC**: Any 3 digits (e.g., 123)

---

## Phase 2: Stripe Connect (Seller Payouts)

### 2.1 Enable Stripe Connect

1. Go to **Stripe Dashboard** → **Connect** → **Settings**
2. Turn on Connect
3. Select **Express** account type
4. Configure branding (logo, colors, etc.)

### 2.2 Seller Onboarding Flow

**Backend is ready!** The system will:
1. Create Connect account: `StripePaymentService::createConnectAccount()`
2. Generate onboarding link: `StripePaymentService::createAccountLink()`
3. Handle webhooks when seller completes onboarding
4. Update `users.stripe_onboarding_complete` flag

**Frontend implementation**: You'll need to create seller onboarding pages (coming in Phase 2)

### 2.3 Platform Fee Structure

Edit `StripePaymentService.php` to set your commission:

```php
// Example: Take 5% platform fee on each sale
$platformFee = $orderTotal * 0.05;
$sellerAmount = $orderTotal - $platformFee;
```

---

## Phase 3: Cryptocurrency Payments (NOWPayments)

### 3.1 Create NOWPayments Account

1. Go to https://nowpayments.io
2. Create account and verify
3. Go to **Settings** → **API** → **API Key**

### 3.2 Configure Environment

Add to `/api/.env`:

```env
# NOWPayments Configuration
NOWPAYMENTS_API_KEY=xxxxxxxxxxxxx
NOWPAYMENTS_IPN_SECRET=xxxxxxxxxxxxx  # For webhook verification
```

### 3.3 Supported Cryptocurrencies

NOWPayments supports 200+ cryptocurrencies:
- Bitcoin (BTC)
- Ethereum (ETH)
- Tether (USDT)
- USD Coin (USDC)
- Litecoin (LTC)
- And many more...

### 3.4 Set Up IPN (Instant Payment Notifications)

1. Go to **NOWPayments Dashboard** → **Settings** → **IPN**
2. Set IPN URL: `https://your-domain.com/api/crypto/webhook`
3. Copy IPN Secret to `.env`

---

## Phase 4: Order Processing & Payment Flow

### 4.1 How Payments Work

**Buyer Flow**:
1. **Deposit** → Funds go to wallet (via Stripe/Crypto/PayPal)
2. **Purchase** → Wallet balance deducted
3. **Split Payment**:
   - Platform fee: 5-10% (your revenue)
   - Seller: 90-95% (goes to their wallet)

**Seller Flow**:
1. Receive payment in wallet
2. Request withdrawal
3. Admin approves (or auto-approve based on rules)
4. Funds transfer via:
   - **Stripe Connect** → Bank account (1-3 days)
   - **PayPal** → PayPal account (instant)
   - **Crypto** → Wallet address (10-60 min)

### 4.2 Test the Full Flow

```bash
# 1. Create test wallets
php artisan migrate:fresh --seed

# 2. Test deposit (Stripe)
curl -X POST http://localhost:8000/api/wallet/deposit \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 100, "payment_method": "stripe"}'

# 3. Complete payment using test card 4242424242424242

# 4. Webhook processes payment → Balance updated

# 5. Make purchase → Wallet deducted, seller receives payment

# 6. Seller requests withdrawal

# 7. Admin approves → Stripe Connect payout initiated
```

---

## Security Checklist

- [X] Webhook signatures verified
- [X] Payment details encrypted (`payment_details` in withdrawal_requests)
- [X] Auth middleware on all wallet routes
- [X] Transaction logging for audit trail
- [X] Balance validation before deductions
- [X] Idempotency checks (prevent duplicate transactions)

---

## Frontend Integration (Next Steps)

### Install Stripe.js

```bash
cd frontend
npm install @stripe/stripe-js
```

### Example: Deposit Page with Stripe Elements

```vue
<script setup>
import { loadStripe } from '@stripe/stripe-js'

const stripe = await loadStripe('pk_test_xxxxxxxxxxxxx')

const handleDeposit = async () => {
  // 1. Create payment intent
  const { client_secret } = await api.post('/api/wallet/deposit', {
    amount: 100,
    payment_method: 'stripe'
  })

  // 2. Confirm payment with Stripe Elements
  const { error } = await stripe.confirmCardPayment(client_secret, {
    payment_method: {
      card: cardElement,
      billing_details: { name: user.name }
    }
  })

  if (error) {
    alert('Payment failed: ' + error.message)
  } else {
    alert('Deposit successful!')
    // Webhook will update balance
  }
}
</script>
```

---

## Production Checklist

### Before Going Live:

1. **Switch to Live Keys**
   - Replace `pk_test_` with `pk_live_`
   - Replace `sk_test_` with `sk_live_`

2. **Update Webhook Endpoint**
   - Point to production URL
   - Update webhook secret

3. **Enable Fraud Prevention**
   - Turn on Stripe Radar
   - Set up 3D Secure for cards

4. **Legal Requirements**
   - Terms of Service
   - Privacy Policy
   - Refund Policy
   - Tax compliance (depending on jurisdiction)

5. **Monitoring**
   - Set up error alerts for failed payments
   - Monitor webhook failures
   - Track suspicious activity

---

## Troubleshooting

### Webhook Not Receiving Events

```bash
# Check webhook logs
tail -f storage/logs/laravel.log | grep "Stripe webhook"

# Test webhook manually
stripe trigger payment_intent.succeeded
```

### Payment Intent Failed

Check Stripe Dashboard → **Payments** → Failed payments for:
- Card declined
- Insufficient funds
- 3D Secure authentication failed

### Balance Not Updating

1. Check webhook logs
2. Verify `STRIPE_WEBHOOK_SECRET` is correct
3. Check transactions table for status
4. Look for duplicate prevention (idempotency)

---

## Support

- **Stripe Docs**: https://stripe.com/docs
- **Stripe Connect Guide**: https://stripe.com/docs/connect
- **NOWPayments API**: https://documenter.getpostman.com/view/7907941/S1a32n38
- **Stripe Testing**: https://stripe.com/docs/testing

---

## Next Implementation Steps

1. ✅ Stripe webhook handler (DONE)
2. ✅ Stripe payment intents (DONE)
3. ✅ Frontend Stripe Elements integration (DONE)
4. ✅ Sanctum authentication fix for API requests (DONE)
5. ⏳ Test complete deposit flow end-to-end
6. ⏳ Seller onboarding UI (Stripe Connect)
7. ⏳ Crypto payment integration (NOWPayments)
8. ⏳ Order processing with splits
9. ⏳ Admin withdrawal approval dashboard
10. ⏳ Automated payouts based on rules

**NOTE**: Test Stripe keys are configured in `.env`. Replace with your real keys from Stripe Dashboard when you create your account.

Ready to continue implementation? Let me know which phase to tackle next!
