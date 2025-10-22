<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Seller - MMO Supply</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
            font-size: 28px;
        }
        .feature {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
        }
        .feature h3 {
            margin-top: 0;
            color: #10b981;
            font-size: 18px;
        }
        .feature p {
            margin: 8px 0 0 0;
            color: #666;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 5px;
            margin: 25px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .tip {
            background-color: #fff9e6;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .tip strong {
            color: #e65100;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            text-align: center;
        }
        .stat {
            flex: 1;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Congrats {{ $userName }}!</h1>
        </div>

        <p>You're now a verified seller on MMO Supply.</p>

        <p>Here's what you'll love about selling with us:</p>

        <div class="feature">
            <h3>💰 Instant Payouts</h3>
            <p>Stripe deposits directly to your account when orders are confirmed. No 7-14 day delays like other marketplaces - get paid immediately.</p>
        </div>

        <div class="feature">
            <h3>💬 Real-Time Buyer Chat</h3>
            <p>Close deals faster with instant messaging. Answer buyer questions immediately, build trust, and sell more. Sellers who respond within 5 minutes sell 3x faster!</p>
        </div>

        <div class="feature">
            <h3>📊 Modern Dashboard</h3>
            <p>Track orders, manage inventory, chat with buyers - all in one place. Clean, fast interface designed for professional sellers.</p>
        </div>

        <div class="feature">
            <h3>🛡️ Zero Chargeback Risk</h3>
            <p>Stripe handles buyer disputes through their Connect platform. You focus on selling, we handle the protection.</p>
        </div>

        <div class="tip">
            <strong>💡 Pro Seller Tip:</strong>
            List items for popular games like Old School RuneScape, Roblox, and other MMOs. The real-time chat feature makes it easy to verify buyers and close sales quickly!
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-number">5min</div>
                <div class="stat-label">Average Response<br>= 3x More Sales</div>
            </div>
            <div class="stat">
                <div class="stat-number">0s</div>
                <div class="stat-label">Payout Delay<br>with Stripe</div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/seller/listings/create" class="button">
                Create Your First Listing
            </a>
        </div>

        <p>Need help getting started? Check out our <a href="{{ config('app.frontend_url') }}/seller-guide" style="color: #10b981;">Seller Guide</a> or reply to this email with any questions.</p>

        <p>Let's make some money! 💸</p>

        <p>
            - The MMO Supply Team<br>
            <small style="color: #666;">Your partner in gaming marketplace success</small>
        </p>

        <div class="footer">
            <p>
                <a href="{{ config('app.frontend_url') }}/seller/dashboard">Seller Dashboard</a> |
                <a href="{{ config('app.frontend_url') }}/seller/listings/create">Create Listing</a> |
                <a href="{{ config('app.frontend_url') }}/support">Get Help</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                You're receiving this email because you became a seller on MMO Supply.
            </p>
        </div>
    </div>
</body>
</html>
