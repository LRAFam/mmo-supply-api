<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Updated</title>
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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background-color: #cfe2ff;
            color: #084298;
        }
        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #842029;
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
        .status-change {
            text-align: center;
            padding: 20px;
            background-color: #e7f6f8;
            border-radius: 8px;
            margin: 25px 0;
        }
        .arrow {
            font-size: 24px;
            color: #06b6d4;
            margin: 0 15px;
        }
        .seller-notes {
            background-color: #fff9e6;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .seller-notes h3 {
            color: #e65100;
            margin-top: 0;
            font-size: 18px;
        }
        .seller-notes-content {
            white-space: pre-wrap;
            background-color: #ffffff;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .button {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0891b2;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
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
        .items-list {
            margin: 20px 0;
        }
        .item {
            background-color: #f8f9fa;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #06b6d4;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order Status Updated</h1>
        </div>

        <p>Hello <strong>{{ $buyerName }}</strong>,</p>

        <p>Your order status has been updated by the seller.</p>

        <div class="order-info">
            <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
            <p><strong>Placed on:</strong> {{ $order->created_at->format('F j, Y') }}</p>
            <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
        </div>

        <div class="status-change">
            <p style="margin-bottom: 15px; font-weight: bold; color: #555;">Status Change</p>
            <div style="display: flex; align-items: center; justify-content: center;">
                <span class="status-badge status-{{ $oldStatus }}">{{ ucfirst($oldStatus) }}</span>
                <span class="arrow">→</span>
                <span class="status-badge status-{{ $newStatus }}">{{ ucfirst($newStatus) }}</span>
            </div>
        </div>

        @if($order->seller_notes)
        <div class="seller-notes">
            <h3>📝 Seller's Notes</h3>
            <div class="seller-notes-content">{{ $order->seller_notes }}</div>
        </div>
        @endif

        <div class="items-list">
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">Order Items</h3>
            @foreach($order->items as $item)
            <div class="item">
                <strong>{{ $item->product_name }}</strong>
                @if($item->game_name)
                <span style="color: #666; font-size: 14px;">- {{ $item->game_name }}</span>
                @endif
                <br>
                <span style="color: #666; font-size: 14px;">Quantity: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}</span>
            </div>
            @endforeach
        </div>

        @if($newStatus === 'processing')
        <div style="background-color: #cfe2ff; border: 1px solid #084298; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #084298;">
                <strong>⏳ In Progress:</strong> Your order is being processed. You'll receive another notification when it's delivered.
            </p>
        </div>
        @elseif($newStatus === 'delivered' || $newStatus === 'completed')
        <div style="background-color: #d1e7dd; border: 1px solid #0f5132; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #0f5132;">
                <strong>✅ Delivered:</strong> Your order has been delivered! If you received delivery details via email, please secure your account immediately.
            </p>
        </div>
        @elseif($newStatus === 'cancelled')
        <div style="background-color: #f8d7da; border: 1px solid #842029; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #842029;">
                <strong>❌ Cancelled:</strong> This order has been cancelled.
            </p>
            @if($order->payment_status === 'refunded')
            <p style="margin: 10px 0 0 0; color: #0f5132; background-color: #d1e7dd; padding: 10px; border-radius: 4px; border: 1px solid #0f5132;">
                <strong>💰 Refund Processed:</strong>
                @if($order->payment_method === 'wallet')
                Your wallet has been credited with ${{ number_format($order->total, 2) }}. The funds are available immediately.
                @elseif($order->payment_method === 'stripe')
                A refund of ${{ number_format($order->total, 2) }} has been initiated to your original payment method. It may take 5-10 business days to appear in your account.
                @else
                A refund of ${{ number_format($order->total, 2) }} has been processed.
                @endif
            </p>
            @endif
            <p style="margin: 10px 0 0 0; color: #842029; font-size: 14px;">
                If you have any questions about this cancellation, please contact the seller or support.
            </p>
        </div>
        @endif

        <div class="button-container">
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </div>

        <p>If you have any questions about this order, you can message the seller through our platform.</p>

        <div class="footer">
            <p>Thank you for using our marketplace!</p>
            <p>
                <a href="{{ config('app.frontend_url') }}">Visit Website</a> |
                <a href="{{ config('app.frontend_url') }}/messages">Messages</a> |
                <a href="{{ config('app.frontend_url') }}/orders">My Orders</a>
            </p>
        </div>
    </div>
</body>
</html>
