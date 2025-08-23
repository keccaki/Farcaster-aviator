<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviator Game - Magic Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 100%);
            margin: 0;
            padding: 0;
            color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #1a2332;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .header {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: white;
            font-size: 28px;
            font-weight: bold;
        }
        .plane-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-text {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #e2e8f0;
        }
        .magic-button {
            display: inline-block;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin: 20px 0;
            transition: transform 0.2s;
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }
        .magic-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 107, 53, 0.4);
        }
        .expiry-notice {
            background: rgba(255, 107, 53, 0.1);
            border: 1px solid rgba(255, 107, 53, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            color: #fbbf24;
        }
        .security-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            font-size: 14px;
            color: #94a3b8;
        }
        .footer {
            background: #0f1419;
            padding: 25px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }
            .content {
                padding: 25px 20px;
            }
            .header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="plane-icon">✈️</div>
            <h1>{{ $appName }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Secure Magic Login</p>
        </div>

        <div class="content">
            <div class="welcome-text">
                <strong>Ready for takeoff?</strong><br>
                Click the button below to instantly access your Aviator account. No password needed!
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="magic-button">
                    🚀 Login to Aviator Game
                </a>
            </div>

            <div class="expiry-notice">
                <strong>⏰ Time Sensitive:</strong> This magic link expires in <strong>{{ $expiryMinutes }} minutes</strong> for your security.
            </div>

            <div class="security-info">
                <strong>🔒 Security Note:</strong><br>
                • This link can only be used once<br>
                • It was requested for: <strong>{{ $email }}</strong><br>
                • If you didn't request this login, please ignore this email<br>
                • Never share this link with anyone
            </div>

            <p style="color: #94a3b8; font-size: 14px; line-height: 1.5;">
                Having trouble with the button? Copy and paste this link into your browser:<br>
                <span style="word-break: break-all; color: #60a5fa;">{{ $loginUrl }}</span>
            </p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $email }} from {{ $appName }}</p>
            <p>If you have any questions, contact our support team.</p>
            <p style="margin-top: 15px; opacity: 0.7;">
                © {{ date('Y') }} Aviator Game. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html> 