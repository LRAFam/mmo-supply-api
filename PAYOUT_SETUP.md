# Payout Systems Setup Guide

This guide covers the setup for all three payout methods: **Stripe Connect**, **PayPal Payouts**, and **NOWPayments (Crypto)**.

---

## Table of Contents
1. [Environment Variables](#environment-variables)
2. [Database Setup](#database-setup)
3. [Stripe Connect Setup](#stripe-connect-setup)
4. [PayPal Payouts Setup](#paypal-payouts-setup)
5. [NOWPayments (Crypto) Setup](#nowpayments-crypto-setup)
6. [Testing](#testing)
7. [Webhooks](#webhooks)

---

## Environment Variables

Add these variables to your `.env` file:

```env
# ============================================
# STRIPE CONNECT (Bank Account Payouts)
# ============================================
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# ============================================
# PAYPAL PAYOUTS
# ============================================
PAYPAL_MODE=sandbox  # Use 'live' for production
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_WEBHOOK_ID=your_webhook_id  # Optional, for webhook signature verification

# ============================================
# NOWPAYMENTS (Cryptocurrency Payouts)
# ============================================
NOWPAYMENTS_API_KEY=your_nowpayments_api_key
NOWPAYMENTS_IPN_SECRET=your_ipn_secret  # Optional, for webhook verification

# ============================================
# APP URLS (Required for webhooks and redirects)
# ============================================
APP_URL=https://api.yourdomain.com
APP_FRONTEND_URL=https://yourdomain.com
```

---

## Database Setup

Run the migration to create the `paypal_payouts` table:

```bash
php artisan migrate
```

This will create the following tables if they don't exist:
- `stripe_payouts` - Stripe Connect payout records
- `crypto_transactions` - NOWPayments crypto transactions
- `paypal_payouts` - PayPal payout records (NEW)

---

## Stripe Connect Setup

### 1. Create a Stripe Account
- Go to [https://stripe.com](https://stripe.com) and create an account
- Complete your business profile

### 2. Get API Keys
1. Go to **Developers** → **API keys**
2. Copy your **Publishable key** (`pk_test_...`) → `STRIPE_KEY`
3. Copy your **Secret key** (`sk_test_...`) → `STRIPE_SECRET`

### 3. Enable Stripe Connect
1. Go to **Connect** → **Settings**
2. Enable **Express** accounts
3. Configure your branding and settings

### 4. Set Up Webhooks
1. Go to **Developers** → **Webhooks**
2. Add endpoint: `https://api.yourdomain.com/api/stripe/webhook`
3. Select events to listen for:
   - `account.updated`
   - `transfer.created`
   - `transfer.failed`
   - `payout.paid`
   - `payout.failed`
4. Copy the **Signing secret** → `STRIPE_WEBHOOK_SECRET`

### 5. Test Mode
- Use test mode for development
- Switch to live mode in production

---

## PayPal Payouts Setup

### 1. Create a PayPal Business Account
- Go to [https://www.paypal.com/business](https://www.paypal.com/business)
- Create a **Business account**

### 2. Create a PayPal App
1. Go to [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/)
2. Click **Apps & Credentials**
3. Switch to **Sandbox** for testing (or **Live** for production)
4. Create a new app:
   - App Name: "MMO.SUPPLY Payouts"
   - App Type: **Merchant**
5. Copy your credentials:
   - **Client ID** → `PAYPAL_CLIENT_ID`
   - **Secret** → `PAYPAL_CLIENT_SECRET`

### 3. Enable Payouts
1. In your app settings, ensure **Payouts** is enabled
2. You may need to contact PayPal support to enable Payouts API access
3. Submit any required business documentation

### 4. Set Up Webhooks (Optional but Recommended)
1. In your app, go to **Webhooks**
2. Add webhook URL: `https://api.yourdomain.com/api/paypal/webhook`
3. Select events:
   - `PAYMENT.PAYOUTS-ITEM.SUCCEEDED`
   - `PAYMENT.PAYOUTS-ITEM.FAILED`
   - `PAYMENT.PAYOUTS-ITEM.BLOCKED`
   - `PAYMENT.PAYOUTS-ITEM.CANCELED`
   - `PAYMENT.PAYOUTS-ITEM.DENIED`
   - `PAYMENT.PAYOUTS-ITEM.REFUNDED`
4. Copy **Webhook ID** → `PAYPAL_WEBHOOK_ID`

### 5. Sandbox Testing
- Test using Sandbox accounts first
- Create sandbox buyer/seller accounts at [https://developer.paypal.com/dashboard/accounts](https://developer.paypal.com/dashboard/accounts)
- Set `PAYPAL_MODE=sandbox` in `.env`

### 6. Go Live
1. Switch to **Live** in PayPal Developer Dashboard
2. Get Live credentials
3. Set `PAYPAL_MODE=live` in `.env`
4. Replace sandbox credentials with live credentials

### 7. Important Limits & Fees
- **Minimum Payout**: $1.00 USD
- **Maximum Payout**: $10,000 USD per transaction (may vary by country)
- **Fee**: 2% per payout (minimum $0.25)
- **Daily Limits**: Vary based on account status and verification
- **Processing Time**: Usually instant, can take up to 30 minutes

---

## NOWPayments (Crypto) Setup

### 1. Create a NOWPayments Account
- Go to [https://nowpayments.io](https://nowpayments.io)
- Sign up and verify your account

### 2. Get API Key
1. Go to **Settings** → **API**
2. Generate an **API Key**
3. Copy it → `NOWPAYMENTS_API_KEY`

### 3. Enable Payouts
1. Contact NOWPayments support to enable **Payouts API**
2. You may need to:
   - Verify your business
   - Provide KYC documentation
   - Set up withdrawal addresses

### 4. Set Up IPN (Instant Payment Notifications)
1. Go to **Settings** → **IPN**
2. Set callback URL: `https://api.yourdomain.com/api/crypto/webhook`
3. Generate **IPN Secret** → `NOWPAYMENTS_IPN_SECRET`

### 5. Supported Cryptocurrencies
NOWPayments supports 200+ cryptocurrencies including:
- Bitcoin (BTC)
- Ethereum (ETH)
- Tether (USDT) - Multiple networks
- USD Coin (USDC)
- Litecoin (LTC)
- Binance Coin (BNB)
- Ripple (XRP)
- Tron (TRX)
- And 190+ more...

### 6. Important Notes
- **Minimum Payout**: Varies by currency (typically $10-50)
- **Network Fees**: Deducted from payout amount
- **Processing Time**: Usually within minutes to 1 hour
- **Some currencies require extra ID**: XRP, XLM, EOS, BNB (Memo/Tag)

---

## Testing

### Test Stripe Connect
```bash
# From frontend
# 1. Go to /wallet/withdraw
# 2. Click "Connect Stripe Account" if not connected
# 3. Complete onboarding (use test data)
# 4. Request a payout
```

### Test PayPal Payouts
```bash
# From frontend
# 1. Go to /wallet/withdraw
# 2. Select "PayPal" method
# 3. Enter sandbox PayPal email
# 4. Request payout
# 5. Check sandbox account for funds
```

### Test NOWPayments Crypto
```bash
# From frontend
# 1. Go to /wallet/withdraw
# 2. Select "Cryptocurrency"
# 3. Choose crypto (e.g., BTC, ETH)
# 4. Enter wallet address
# 5. Request payout
# 6. Check wallet for funds
```

### API Testing with cURL

**Stripe Connect Payout:**
```bash
curl -X POST https://api.yourdomain.com/api/stripe/connect/payout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 50.00}'
```

**PayPal Payout:**
```bash
curl -X POST https://api.yourdomain.com/api/payouts/paypal \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50.00,
    "paypal_email": "test@example.com",
    "account_holder": "John Doe"
  }'
```

**Crypto Payout:**
```bash
curl -X POST https://api.yourdomain.com/api/crypto/payout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50.00,
    "currency": "btc",
    "wallet_address": "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"
  }'
```

---

## Webhooks

### Webhook URLs
Make sure these URLs are publicly accessible:

- **Stripe**: `https://api.yourdomain.com/api/stripe/webhook`
- **PayPal**: `https://api.yourdomain.com/api/paypal/webhook`
- **NOWPayments**: `https://api.yourdomain.com/api/crypto/webhook`

### Testing Webhooks Locally

Use a service like **ngrok** to expose your local server:

```bash
# Install ngrok
brew install ngrok

# Run ngrok
ngrok http 8000

# Use the ngrok URL for webhooks
https://abc123.ngrok.io/api/stripe/webhook
```

### Webhook Security

All webhook endpoints implement security verification:

- **Stripe**: Signature verification using `STRIPE_WEBHOOK_SECRET`
- **PayPal**: Signature verification using `PAYPAL_WEBHOOK_ID` (optional)
- **NOWPayments**: IPN signature verification using `NOWPAYMENTS_IPN_SECRET` (optional)

---

## Troubleshooting

### Stripe Connect Issues
- **Account not verified**: User needs to complete Stripe onboarding
- **Payouts disabled**: Check account status in Stripe Dashboard
- **Transfer failed**: Insufficient platform balance

### PayPal Issues
- **Payout failed - Invalid email**: Recipient doesn't have a PayPal account
- **Payout blocked**: Account verification required
- **API not enabled**: Contact PayPal support to enable Payouts API
- **Rate limit exceeded**: Wait and retry (PayPal has strict limits)

### NOWPayments Issues
- **Payout API not enabled**: Contact support to enable
- **Minimum amount not met**: Check currency minimum
- **Invalid wallet address**: Verify address format
- **Network fee too high**: Try different currency

---

## Production Checklist

Before going live, ensure:

### Stripe Connect
- [ ] Switch from test to live API keys
- [ ] Configure live webhook endpoint
- [ ] Test with real Stripe account
- [ ] Verify webhook signing secret

### PayPal
- [ ] Set `PAYPAL_MODE=live`
- [ ] Replace sandbox credentials with live credentials
- [ ] Test with real PayPal account
- [ ] Verify business account is approved for Payouts
- [ ] Set up live webhook endpoint

### NOWPayments
- [ ] Complete account verification/KYC
- [ ] Payouts API enabled by support
- [ ] Test with small amounts first
- [ ] Verify IPN callback URL
- [ ] Check minimum payout amounts for each currency

### General
- [ ] All environment variables set correctly
- [ ] Database migrations run
- [ ] Webhook endpoints are HTTPS
- [ ] Error logging configured
- [ ] Monitor payout success rates
- [ ] Set up alerts for failed payouts

---

## Support & Resources

### Stripe
- Documentation: [https://stripe.com/docs/connect](https://stripe.com/docs/connect)
- Support: [https://support.stripe.com](https://support.stripe.com)

### PayPal
- Documentation: [https://developer.paypal.com/docs/payouts/](https://developer.paypal.com/docs/payouts/)
- Support: [https://www.paypal.com/us/smarthelp/contact-us](https://www.paypal.com/us/smarthelp/contact-us)

### NOWPayments
- Documentation: [https://documenter.getpostman.com/view/7907941/S1a32n38](https://documenter.getpostman.com/view/7907941/S1a32n38)
- Support: [support@nowpayments.io](mailto:support@nowpayments.io)

---

## License

This implementation is part of the MMO.SUPPLY platform.

---

**Last Updated**: October 2025
