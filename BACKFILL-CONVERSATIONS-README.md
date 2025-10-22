# Backfill Order Conversations

## Problem

Orders created before the messaging system was implemented don't have conversations. This means:
- Buyers and sellers can't message each other about old orders
- The order detail page crashes when trying to access `order.conversation`

## Solution

Run the backfill command to create conversations for all existing orders:

```bash
php artisan orders:backfill-conversations
```

This command:
1. Finds all orders without conversations
2. Creates a conversation for each order between the buyer and seller
3. Shows progress bar and summary

## Usage

```bash
# Run on production
cd /path/to/api
php artisan orders:backfill-conversations
```

Output example:
```
🔍 Finding orders without conversations...
Found 12 orders without conversations
 12/12 [============================] 100%

✅ Successfully created 12 conversations
```

## What It Does

- Only creates conversations for orders that have a `seller_id` (skips cart-only orders)
- Uses `Conversation::findOrCreate()` to avoid duplicates
- Shows progress bar for large batches
- Handles errors gracefully and reports them

## After Running

All old orders will have conversations and messaging will work properly!

## Rate Limiting Issue

**Problem**: Resend API is rate-limited to 2 emails per second. The queue worker was sending emails too fast and hitting the rate limit.

**Solution**: Run the queue worker with `--sleep=1` to add a 1-second delay between jobs:

```bash
# Update your supervisor/deployment script to use:
php artisan queue:work --sleep=1 --daemon
```

This ensures emails are sent at most 1 per second, well under the 2/second limit.

### For Forge/Supervisor

Update your queue worker configuration:
```ini
[program:mmo-supply-queue]
command=php /home/forge/api.mmo.supply/current/artisan queue:work --sleep=1 --daemon
autostart=true
autorestart=true
```

Then restart the worker:
```bash
sudo supervisorctl restart mmo-supply-queue
```
