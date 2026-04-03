<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .code-box {
            background-color: #ecf0f1;
            border: 2px solid #3498db;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            letter-spacing: 5px;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-radius: 0 0 5px 5px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 SATAAB Hotel</h1>
            <p>Telegram Account Verification</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>

            <p>You've requested to link your Telegram account to your SATAAB Hotel account.</p>

            <p>Please use the following verification code in Telegram:</p>

            <div class="code-box">
                <div class="code">{{ $code }}</div>
            </div>

            <p><strong>Code expires in:</strong> {{ $expiresIn }}</p>

            <div class="warning">
                <strong>⚠️ Security Notice:</strong>
                <p>Never share this code with anyone. Our staff will never ask for this code.</p>
            </div>

            <p>If you didn't request this verification, please ignore this email.</p>

            <p>
                Best regards,<br>
                <strong>SATAAB Hotel Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} SATAAB Hotel. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
