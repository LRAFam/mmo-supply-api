# Discord Integration - Backend Implementation

## Overview

This document describes the backend implementation for Discord bot integration, allowing subscribers to receive personalized notifications in their Discord servers.

## What Was Implemented

### 1. Database Migration

**File**: `database/migrations/2025_10_15_122455_add_discord_fields_to_users_table.php`

Added the following fields to the `users` table:
- `discord_verification_code` - Temporary code for verifying Discord registration
- `discord_verification_code_expires_at` - Expiration timestamp for verification code
- `discord_guild_id` - Discord server ID where user registered the bot
- `discord_channel_id` - Channel ID for receiving notifications
- `discord_registered_at` - When the user registered their Discord server
- `discord_notifications_enabled` - Toggle for enabling/disabling notifications

### 2. User Model Updates

**File**: `app/Models/User.php`

Added methods:
- `generateDiscordVerificationCode()` - Generate a 10-character verification code
- `verifyDiscordCode($code, $guildId)` - Verify code and register Discord server
- `hasDiscordNotificationsEnabled()` - Check if user can receive notifications
- `updateDiscordChannel($channelId)` - Update notification channel
- `disableDiscordNotifications()` - Disable notifications
- `enableDiscordNotifications()` - Enable notifications

### 3. Discord Bot Controller

**File**: `app/Http/Controllers/DiscordBotController.php`

Added three new endpoints:

#### POST `/api/discord/verify`
Verify a user's Discord registration using their verification code.

**Request**:
```json
{
  "username": "user123",
  "verification_code": "ABC123XYZ",
  "guild_id": "123456789"
}
```

**Response** (Success):
```json
{
  "success": true,
  "subscription_tier": "premium",
  "user_id": 123
}
```

**Response** (Failure):
```json
{
  "success": false,
  "error": "Invalid or expired verification code"
}
```

#### POST `/api/discord/generate-code` (Auth Required)
Generate a new verification code for the authenticated user.

**Response**:
```json
{
  "success": true,
  "verification_code": "ABC123XYZ",
  "expires_at": "2025-10-16T12:24:55.000000Z"
}
```

#### GET `/api/discord/status` (Auth Required)
Get Discord registration status for the authenticated user.

**Response**:
```json
{
  "success": true,
  "is_registered": true,
  "guild_id": "123456789",
  "channel_id": "987654321",
  "registered_at": "2025-10-15T12:24:55.000000Z",
  "notifications_enabled": true,
  "has_active_subscription": true,
  "subscription_tier": "premium"
}
```

### 4. Discord Webhook Service Updates

**File**: `app/Services/DiscordWebhookService.php`

Added six new methods for user-specific notifications:

#### `sendUserSale($user, $sale, $listing, $buyer)`
Send notification when user makes a sale.

#### `sendUserMessage($user, $message, $sender)`
Send notification when user receives a message.

#### `sendUserOrder($user, $order, $listing, $buyer)`
Send notification when user receives an order.

#### `sendUserOffer($user, $offer, $listing, $buyer)`
Send notification when user receives an offer.

#### `sendUserReview($user, $review, $listing, $reviewer)`
Send notification when user receives a review.

#### `sendLowStockAlert($user, $listing, $currentStock, $threshold)`
Send notification when listing stock is low.

All methods:
- Check if user has Discord notifications enabled
- Check if user has active subscription
- Send webhook to configured Discord bot URL
- Handle errors gracefully

### 5. API Routes

**File**: `routes/api.php`

Added new routes:
```php
Route::prefix('discord')->group(function () {
    // Public verification endpoint (for bot)
    Route::post('/verify', [DiscordBotController::class, 'verifyDiscord']);

    // Protected endpoints (require user auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/generate-code', [DiscordBotController::class, 'generateVerificationCode']);
        Route::get('/status', [DiscordBotController::class, 'getDiscordStatus']);
    });
});
```

## Configuration

### Environment Variables

The following variables are already configured in `.env.example`:

```env
# Discord Bot Webhooks
DISCORD_WEBHOOK_URL=http://localhost:3001/webhooks
DISCORD_WEBHOOK_SECRET=your_secret_webhook_key_here
```

**For Production**:
- Set `DISCORD_WEBHOOK_URL` to your Discord bot's webhook server URL
- Generate a strong random secret for `DISCORD_WEBHOOK_SECRET`

### Service Configuration

The Discord webhook service reads configuration from `config/services.php`:

```php
'discord' => [
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    'webhook_secret' => env('DISCORD_WEBHOOK_SECRET'),
],
```

## Integration Examples

### Example 1: Send Sale Notification

In your order completion logic (e.g., `OrderController@complete`):

```php
use App\Services\DiscordWebhookService;

// After order is completed
$discordService = new DiscordWebhookService();
$discordService->sendUserSale(
    $seller,        // User model
    $sale,          // Sale/Order model
    $listing,       // Listing model
    $buyer          // User model
);
```

### Example 2: Send Message Notification

In your message sending logic (e.g., `MessageController@send`):

```php
use App\Services\DiscordWebhookService;

// After message is sent
$recipient = User::find($message->recipient_id);
$discordService = new DiscordWebhookService();
$discordService->sendUserMessage(
    $recipient,     // User model (recipient)
    $message,       // Message model
    $sender         // User model (sender)
);
```

### Example 3: Send Low Stock Alert

In your stock management logic or a scheduled task:

```php
use App\Services\DiscordWebhookService;

// Check all listings with low stock
$lowStockListings = Item::where('stock', '<=', 5)->get();

$discordService = new DiscordWebhookService();
foreach ($lowStockListings as $listing) {
    $discordService->sendLowStockAlert(
        $listing->user,         // User model (seller)
        $listing,               // Listing model
        $listing->stock,        // Current stock
        5                       // Threshold
    );
}
```

### Example 4: Send Review Notification

In your review creation logic (e.g., `ReviewController@store`):

```php
use App\Services\DiscordWebhookService;

// After review is created
$seller = $review->listing->user;
$discordService = new DiscordWebhookService();
$discordService->sendUserReview(
    $seller,        // User model (seller receiving review)
    $review,        // Review model
    $review->listing, // Listing model
    $review->user   // User model (reviewer)
);
```

## Frontend Integration

### User Flow for Registering Discord

1. **User navigates to account settings → Discord Integration**
2. **Frontend calls** `POST /api/discord/generate-code` (authenticated)
3. **Display verification code** to user with expiration time
4. **User follows invite link** to add bot to their Discord server
5. **User runs** `/register username:their_username verification_code:CODE` in Discord
6. **Discord bot calls** `POST /api/discord/verify` to verify the code
7. **Bot confirms registration** to user in Discord

### Checking Registration Status

```javascript
// Frontend code example
const checkDiscordStatus = async () => {
  const response = await fetch('/api/discord/status', {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  const data = await response.json();

  if (data.is_registered) {
    console.log('Discord is connected!');
    console.log('Server ID:', data.guild_id);
    console.log('Notifications:', data.notifications_enabled ? 'ON' : 'OFF');
  }
};
```

## Testing

### Test Verification Flow

```bash
# 1. Generate a verification code (authenticated request)
curl -X POST http://localhost:8000/api/discord/generate-code \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# 2. Verify the code (from Discord bot)
curl -X POST http://localhost:8000/api/discord/verify \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "verification_code": "ABC123XYZ",
    "guild_id": "123456789"
  }'

# 3. Check status (authenticated request)
curl http://localhost:8000/api/discord/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Webhook Notifications

```php
// In tinker or a test script
php artisan tinker

$user = User::first();
$user->update([
    'discord_guild_id' => '123456789',
    'discord_notifications_enabled' => true,
]);

$discordService = new \App\Services\DiscordWebhookService();

// Test sale notification
$discordService->sendUserSale($user, $sale, $listing, $buyer);
```

## Security Considerations

1. **Verification Codes**:
   - Expire after 24 hours
   - Single-use only (cleared after verification)
   - 10 characters long with high entropy

2. **Subscription Check**:
   - Only users with active subscriptions can register
   - Notifications only sent if subscription is active
   - Automatic cutoff if subscription lapses

3. **Webhook Authentication**:
   - All webhooks require secret key in header
   - Graceful handling of failures (logged but not blocking)

4. **Rate Limiting**:
   - Consider adding rate limiting to prevent webhook spam
   - Discord bot should implement its own rate limiting

## Monitoring

### Logs to Monitor

- Discord webhook send attempts (Info level)
- Discord webhook failures (Error level)
- Verification code generation (Info level)
- Registration events (Info level)

### Metrics to Track

- Number of users with Discord registered
- Discord notification success/failure rate
- Most common notification types sent
- Average notifications per user per day

## Troubleshooting

### Notifications Not Sending

1. Check `DISCORD_WEBHOOK_URL` is set correctly
2. Verify `DISCORD_WEBHOOK_SECRET` matches between API and bot
3. Ensure user has active subscription
4. Check user has `discord_notifications_enabled = true`
5. Check user has `discord_guild_id` set
6. Review Laravel logs for webhook errors

### Verification Failing

1. Check code hasn't expired (24 hour limit)
2. Verify username matches (checks both `username` and `name` fields)
3. Ensure user has active subscription
4. Check code hasn't already been used

## Future Enhancements

1. **Notification Preferences**: Per-notification-type toggles
2. **Multiple Channels**: Different notification types to different channels
3. **Notification Batching**: Group similar notifications
4. **Statistics Dashboard**: Show notification metrics to users
5. **Webhook Retry Logic**: Automatic retry with exponential backoff
6. **Event Observers**: Use Laravel observers to automatically trigger notifications

---

**Implementation Complete** ✅

The backend is now fully set up to support Discord bot integration for subscriber notifications!
