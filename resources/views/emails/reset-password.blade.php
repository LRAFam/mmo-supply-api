<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
            padding: 40px;
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
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .content {
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
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
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: #06b6d4;
            text-decoration: none;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #06b6d4;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🔐</div>
            <h1>Reset Your Password</h1>
        </div>

        <div class="content">
            <p>Hello,</p>

            <p>You requested to reset your password for your account. Click the button below to choose a new password.</p>

            <div class="info-box">
                <p style="margin: 0;"><strong>📧 Account Email:</strong> {{ $email }}</p>
            </div>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">
                    Reset Password
                </a>
            </div>

            <p style="font-size: 14px; color: #666;">
                If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
            </p>

            <div class="warning-box">
                <strong>⚠️ Security Notes:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This reset link will expire in 60 minutes</li>
                    <li>For security, this link can only be used once</li>
                    <li>If you continue having issues, please contact support</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>
            <p style="margin-top: 20px;">
                <a href="{{ config('app.frontend_url') }}">Visit Website</a> |
                <a href="{{ config('app.frontend_url') }}/support">Support</a>
            </p>
        </div>
    </div>
</body>
</html>
