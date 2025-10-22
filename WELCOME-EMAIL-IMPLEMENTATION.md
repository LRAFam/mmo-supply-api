# Welcome Email Implementation Guide

**Created**: October 22, 2025
**Status**: Ready to implement
**Emails**: WelcomeBuyerMail, WelcomeSellerMail

---

## Overview

Game-agnostic welcome emails that highlight real-time features while conditionally emphasizing OSRS when relevant.

### Features:
- ✅ Real-time chat with sellers
- ✅ Stripe escrow protection
- ✅ Instant payouts (sellers)
- ✅ Modern dashboard
- ✅ Conditional OSRS messaging
- ✅ Supports all games organically

---

## How to Use

### 1. Send Welcome Email to New Buyer

```php
use App\Mail\WelcomeBuyerMail;
use Illuminate\Support\Facades\Mail;

// Option 1: Generic welcome (no game preference)
Mail::to($user->email)->queue(new WelcomeBuyerMail($user));

// Option 2: User interested in specific game
Mail::to($user->email)->queue(new WelcomeBuyerMail($user, 'Old School RuneScape'));

// Option 3: User interested in Roblox
Mail::to($user->email)->queue(new WelcomeBuyerMail($user, 'Roblox'));
```

### 2. Send Welcome Email to New Seller

```php
use App\Mail\WelcomeSellerMail;
use Illuminate\Support\Facades\Mail;

Mail::to($user->email)->queue(new WelcomeSellerMail($user));
```

---

## Integration Points

### When to Send Buyer Welcome Email:

#### Option 1: On Registration (Recommended)
```php
// In your AuthController or similar

use App\Mail\WelcomeBuyerMail;
use Illuminate\Support\Facades\Mail;

public function register(Request $request)
{
    // ... create user ...

    // Send welcome email (queued automatically)
    Mail::to($user->email)->queue(new WelcomeBuyerMail($user));

    return response()->json(['user' => $user]);
}
```

#### Option 2: On Email Verification
```php
// In your email verification handler

use App\Mail\WelcomeBuyerMail;

public function verify($id, Request $request)
{
    // ... verify email ...

    // Send welcome email after verification
    Mail::to($user->email)->queue(new WelcomeBuyerMail($user));

    return redirect('/');
}
```

#### Option 3: On First Login (if not sent on registration)
```php
// In login handler

if (!$user->welcome_email_sent) {
    Mail::to($user->email)->queue(new WelcomeBuyerMail($user));
    $user->update(['welcome_email_sent' => true]);
}
```

---

### When to Send Seller Welcome Email:

#### On Seller Approval
```php
// When user becomes seller or is approved

use App\Mail\WelcomeSellerMail;

public function approveSeller($userId)
{
    $user = User::findOrFail($userId);
    $user->update(['is_seller' => true]);

    // Send seller welcome email
    Mail::to($user->email)->queue(new WelcomeSellerMail($user));

    return response()->json(['message' => 'Seller approved']);
}
```

---

## Tracking User Game Preference

### Option 1: Track from Browsing Behavior
```php
// When user browses a specific game page
// Store in session or user preferences

// In GameController or similar:
public function showGamePage($gameSlug)
{
    $game = Game::where('slug', $gameSlug)->firstOrFail();

    // Track user interest
    if (auth()->check()) {
        auth()->user()->update([
            'last_browsed_game' => $game->name
        ]);
    }

    return view('games.show', compact('game'));
}

// Then in registration:
$interestedGame = $user->last_browsed_game ?? null;
Mail::to($user->email)->queue(new WelcomeBuyerMail($user, $interestedGame));
```

### Option 2: Ask During Registration
```php
// Include game preference in registration form

public function register(Request $request)
{
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $interestedGame = $request->interested_game; // 'Old School RuneScape', 'Roblox', etc.

    Mail::to($user->email)->queue(new WelcomeBuyerMail($user, $interestedGame));

    return response()->json(['user' => $user]);
}
```

### Option 3: Detect from Referrer/UTM
```php
// Track from URL parameters

// Example: /register?game=osrs
public function register(Request $request)
{
    $user = User::create([...]);

    $gameMap = [
        'osrs' => 'Old School RuneScape',
        'roblox' => 'Roblox',
        'rs3' => 'RuneScape 3',
        // ... other games
    ];

    $interestedGame = $gameMap[$request->query('game')] ?? null;

    Mail::to($user->email)->queue(new WelcomeBuyerMail($user, $interestedGame));

    return response()->json(['user' => $user]);
}
```

---

## Email Variants

### Buyer Email Variations:

#### 1. Generic (No Game Preference)
```php
Mail::to($user->email)->queue(new WelcomeBuyerMail($user));
```
**Result**: Generic gaming marketplace messaging

#### 2. OSRS Focused
```php
Mail::to($user->email)->queue(new WelcomeBuyerMail($user, 'Old School RuneScape'));
```
**Result**:
- OSRS-specific tip about gold/account trading
- "Browse OSRS Items" CTA
- Links to OSRS category

#### 3. Other Game Focused
```php
Mail::to($user->email)->queue(new WelcomeBuyerMail($user, 'Roblox'));
```
**Result**:
- Generic pro tip about real-time chat
- "Browse Roblox Items" CTA
- Links to Roblox category

---

## Testing the Emails

### 1. Send Test Email (Artisan Tinker)
```bash
php artisan tinker

# Test buyer welcome (generic)
$user = User::first();
Mail::to('your-email@example.com')->send(new \App\Mail\WelcomeBuyerMail($user));

# Test buyer welcome (OSRS)
Mail::to('your-email@example.com')->send(new \App\Mail\WelcomeBuyerMail($user, 'Old School RuneScape'));

# Test seller welcome
Mail::to('your-email@example.com')->send(new \App\Mail\WelcomeSellerMail($user));
```

### 2. Preview Email in Browser
```bash
php artisan make:command PreviewEmails
```

Then add to the command:
```php
public function handle()
{
    $user = User::first();

    // Render to browser
    $mailable = new \App\Mail\WelcomeBuyerMail($user, 'Old School RuneScape');
    return $mailable->render();
}
```

### 3. Use Mailtrap (Recommended for Development)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

---

## Database Migrations (Optional)

If you want to track which welcome emails have been sent:

```php
// Create migration:
php artisan make:migration add_welcome_email_tracking_to_users_table

// In migration:
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->timestamp('buyer_welcome_sent_at')->nullable();
        $table->timestamp('seller_welcome_sent_at')->nullable();
        $table->string('last_browsed_game')->nullable();
    });
}
```

Then track when sent:
```php
// After sending
$user->update([
    'buyer_welcome_sent_at' => now()
]);
```

---

## Customization

### Change Email Subject
Edit the `envelope()` method in Mail classes:

```php
// In WelcomeBuyerMail.php
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Welcome to MMO Supply - Start Trading!', // Change this
    );
}
```

### Update Email Content
Edit the Blade templates:
- `resources/views/emails/welcome-buyer.blade.php`
- `resources/views/emails/welcome-seller.blade.php`

### Change Button Colors
In the `<style>` section of the Blade templates:
```css
.button {
    background-color: #06b6d4; /* Change this */
}
```

---

## Production Checklist

### Before Deploying:
- [ ] Update `config('app.frontend_url')` in .env
- [ ] Configure mail settings (SMTP, SES, etc.)
- [ ] Test email delivery to real addresses
- [ ] Verify links work (signup, browse, dashboard)
- [ ] Check email rendering in Gmail, Outlook, Apple Mail
- [ ] Ensure queue worker is running (emails are queued)

### Queue Configuration:
The emails use `ShouldQueue` interface, so they're automatically queued. Make sure:
- [ ] Queue worker is running: `php artisan queue:work`
- [ ] Queue connection is configured: `QUEUE_CONNECTION=database`

---

## Examples

### Complete Registration Flow:
```php
// In AuthController.php

use App\Mail\WelcomeBuyerMail;
use Illuminate\Support\Facades\Mail;

public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    // Track interested game if provided
    $interestedGame = $request->query('game');
    $gameMap = [
        'osrs' => 'Old School RuneScape',
        'roblox' => 'Roblox',
    ];
    $gameName = $gameMap[$interestedGame] ?? null;

    // Send welcome email (queued automatically)
    Mail::to($user->email)->queue(new WelcomeBuyerMail($user, $gameName));

    // Track that we sent it
    $user->update(['buyer_welcome_sent_at' => now()]);

    // Log in user
    auth()->login($user);

    return response()->json([
        'user' => $user,
        'token' => $user->createToken('auth-token')->plainTextToken
    ]);
}
```

### Complete Seller Approval Flow:
```php
// In SellerController.php

use App\Mail\WelcomeSellerMail;
use Illuminate\Support\Facades\Mail;

public function approveSeller(Request $request, $userId)
{
    $user = User::findOrFail($userId);

    // Make user a seller
    $user->update([
        'is_seller' => true,
        'seller_approved_at' => now()
    ]);

    // Send welcome email
    Mail::to($user->email)->queue(new WelcomeSellerMail($user));

    // Track that we sent it
    $user->update(['seller_welcome_sent_at' => now()]);

    return response()->json([
        'message' => 'Seller approved successfully',
        'user' => $user
    ]);
}
```

---

## Troubleshooting

### Emails Not Sending?
1. Check queue worker is running: `php artisan queue:work`
2. Check mail configuration in `.env`
3. Check logs: `storage/logs/laravel.log`
4. Try sending sync (non-queued): `Mail::to($email)->send(new WelcomeBuyerMail($user))`

### Email Looks Broken?
1. Test in multiple email clients
2. Use an email testing service (Litmus, Email on Acid)
3. Avoid complex CSS (some clients strip it)
4. Use inline styles for better compatibility

### Links Not Working?
1. Check `config('app.frontend_url')` is set correctly
2. Verify routes exist in frontend
3. Test with absolute URLs

---

## Next Steps

1. **Decide when to send**: Registration? First login? Email verification?
2. **Track game preference**: From browsing? UTM params? Registration form?
3. **Test thoroughly**: Send test emails to yourself
4. **Monitor delivery**: Check bounce rates, open rates
5. **Iterate**: Update copy based on user feedback

---

**Files Created**:
- `app/Mail/WelcomeBuyerMail.php`
- `app/Mail/WelcomeSellerMail.php`
- `resources/views/emails/welcome-buyer.blade.php`
- `resources/views/emails/welcome-seller.blade.php`

**Committed**: October 22, 2025 (Commit: `dfe665a`)
**Branch**: `dev`
