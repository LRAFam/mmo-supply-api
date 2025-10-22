<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to MMO Supply</title>
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
            border-bottom: 3px solid #06b6d4;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #06b6d4;
            margin: 0;
            font-size: 28px;
        }
        .feature {
            background-color: #f8f9fa;
            border-left: 4px solid #06b6d4;
            padding: 15px;
            margin: 20px 0;
        }
        .feature h3 {
            margin-top: 0;
            color: #06b6d4;
            font-size: 18px;
        }
        .feature p {
            margin: 8px 0 0 0;
            color: #666;
        }
        .button {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 5px;
            margin: 25px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background-color: #0891b2;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: #06b6d4;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👋 Welcome to MMO Supply!</h1>
        </div>

        <p>Hey <strong>{{ $userName }}</strong>,</p>

        <p>Welcome to MMO Supply! You're about to experience @if($isOSRS)RuneScape@elseif($interestedGame){{ $interestedGame }}@else gaming@endif trading the modern way.</p>

        <p>Here's what makes us different:</p>

        <div class="feature">
            <h3>💬 Real-Time Chat</h3>
            <p>Chat directly with sellers in your browser. No downloads, no waiting. Get instant answers to your questions and coordinate delivery in real-time.</p>
        </div>

        <div class="feature">
            <h3>🛡️ Stripe Escrow Protection</h3>
            <p>Your payment is protected by Stripe Connect - we never touch your money. Funds are held securely by Stripe until you confirm delivery.</p>
        </div>

        <div class="feature">
            <h3>⚡ Instant Everything</h3>
            <p>From browsing to delivery, we've removed the friction. Real-time order updates, instant messaging, and immediate notifications keep you in control.</p>
        </div>

        @if($isOSRS)
        <div class="tip">
            <strong>💡 OSRS Trader Tip:</strong>
            Sellers who respond within 5 minutes typically deliver faster. Our real-time chat makes it easy to verify legitimacy before buying gold, accounts, or items!
        </div>
        @elseif($interestedGame)
        <div class="tip">
            <strong>💡 Pro Tip:</strong>
            Use our real-time chat to ask sellers questions before purchasing. Instant communication = faster trades and more confidence in your purchase!
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}@if($interestedGame)/games/{{ strtolower(str_replace(' ', '-', $interestedGame)) }}@else/games@endif" class="button">
                @if($isOSRS)
                    Browse OSRS Items
                @elseif($interestedGame)
                    Browse {{ $interestedGame }} Items
                @else
                    Start Browsing
                @endif
            </a>
        </div>

        <p>Questions? Just hit reply - we respond fast (because we practice what we preach 😉).</p>

        <p>Happy trading!</p>

        <p>
            - The MMO Supply Team<br>
            <small style="color: #666;">Building the future of gaming marketplaces</small>
        </p>

        <div class="footer">
            <p>
                <a href="{{ config('app.frontend_url') }}">Browse Marketplace</a> |
                <a href="{{ config('app.frontend_url') }}/how-it-works">How It Works</a> |
                <a href="{{ config('app.frontend_url') }}/support">Get Help</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                You're receiving this email because you signed up for MMO Supply.
            </p>
        </div>
    </div>
</body>
</html>
