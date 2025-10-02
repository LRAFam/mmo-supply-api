<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
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
        .order-info {
            background-color: #f8f9fa;
            border-left: 4px solid #06b6d4;
            padding: 15px;
            margin: 20px 0;
        }
        .order-info p {
            margin: 8px 0;
        }
        .delivery-details {
            background-color: #fff9e6;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .delivery-details h3 {
            color: #e65100;
            margin-top: 0;
            font-size: 18px;
        }
        .delivery-content {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            word-break: break-word;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 12px;
            margin: 20px 0;
            color: #856404;
        }
        .warning strong {
            display: block;
            margin-bottom: 5px;
        }
        .button {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0891b2;
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
            <h1>🎮 Order Delivered!</h1>
        </div>

        <p>Hello <strong>{{ $buyerName }}</strong>,</p>

        <p>Great news! Your order has been delivered by <strong>{{ $sellerName }}</strong>.</p>

        <div class="order-info">
            <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
            <p><strong>Product:</strong> {{ $orderItem->product_name }}</p>
            @if($orderItem->game_name)
            <p><strong>Game:</strong> {{ $orderItem->game_name }}</p>
            @endif
            <p><strong>Quantity:</strong> {{ $orderItem->quantity }}</p>
            <p><strong>Total:</strong> ${{ number_format($orderItem->total, 2) }}</p>
        </div>

        <div class="delivery-details">
            <h3>📦 Delivery Details</h3>
            <div class="delivery-content">{{ $deliveryDetails }}</div>
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Notice:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Change any passwords immediately after receiving account credentials</li>
                <li>Enable two-factor authentication if available</li>
                <li>Never share your account details with anyone</li>
                <li>Keep this email secure and delete it after securing your account</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </div>

        <p>If you have any issues with this delivery, please contact the seller through our messaging system or reach out to our support team.</p>

        <div class="footer">
            <p>Thank you for using our marketplace!</p>
            <p>
                <a href="{{ config('app.frontend_url') }}">Visit Website</a> |
                <a href="{{ config('app.frontend_url') }}/messages">Messages</a> |
                <a href="{{ config('app.frontend_url') }}/support">Support</a>
            </p>
        </div>
    </div>
</body>
</html>
