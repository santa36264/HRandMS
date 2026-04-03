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
            background-color: #27ae60;
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
        .credentials-box {
            background-color: #ecf0f1;
            border: 2px solid #27ae60;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 3px;
        }
        .label {
            font-weight: bold;
            color: #27ae60;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-radius: 0 0 5px 5px;
        }
        .info-box {
            background-color: #d1ecf1;
            border: 1px solid #0c5460;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 SATAAB Hotel</h1>
            <p>Welcome to Our Hotel Family!</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>

            <p>Welcome to SATAAB Hotel! Your account has been successfully created and linked to your Telegram account.</p>

            <p>Here are your account details:</p>

            <div class="credentials-box">
                <div class="credential-item">
                    <span class="label">Email:</span> {{ $user->email }}
                </div>
                <div class="credential-item">
                    <span class="label">Temporary Password:</span> <code>{{ $tempPassword }}</code>
                </div>
            </div>

            <div class="info-box">
                <strong>ℹ️ Important:</strong>
                <p>We've generated a temporary password for you. You can change it anytime in your profile settings after logging in.</p>
            </div>

            <p>You can now:</p>
            <ul>
                <li>Browse and book rooms through our website or Telegram bot</li>
                <li>Manage your bookings</li>
                <li>View your booking history</li>
                <li>Receive exclusive offers and promotions</li>
            </ul>

            <p>
                <a href="{{ $loginUrl }}" class="button">Login to Your Account</a>
            </p>

            <p>
                <strong>Next Steps:</strong>
            </p>
            <ol>
                <li>Visit our website and log in with your email and temporary password</li>
                <li>Change your password to something secure</li>
                <li>Complete your profile information</li>
                <li>Start booking rooms!</li>
            </ol>

            <p>
                If you have any questions or need assistance, please don't hesitate to contact our support team.
            </p>

            <p>
                Best regards,<br>
                <strong>SATAAB Hotel Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} SATAAB Hotel. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>
                <strong>Contact Us:</strong> info@sataabhotel.com | +251 911 234 567
            </p>
        </div>
    </div>
</body>
</html>
