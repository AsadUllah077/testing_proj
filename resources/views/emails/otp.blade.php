<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Verification Code</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }

        .message {
            font-size: 15px;
            color: #555555;
            margin-bottom: 30px;
        }

        .otp-box {
            background-color: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }

        .otp-label {
            font-size: 13px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }

        .validity {
            font-size: 13px;
            color: #888888;
            margin-top: 10px;
        }

        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .info-box p {
            font-size: 14px;
            color: #856404;
            margin: 0;
        }

        .security-note {
            font-size: 14px;
            color: #666666;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eeeeee;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            font-size: 13px;
            color: #888888;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .content {
                padding: 30px 20px;
            }

            .otp-code {
                font-size: 28px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>ERP System</h1>
            <p>Secure Login Verification</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello,
            </div>

            <div class="message">
                You have requested to log in to the ERP System. Please use the following One-Time Password (OTP) to
                complete your login:
            </div>

            <!-- OTP Box -->
            <div class="otp-box">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $details['code'] }}</div>
                <div class="validity">⏱ Valid for 10 minutes</div>
            </div>

            <!-- Important Info -->
            <div class="info-box">
                <p><strong>Important:</strong> If you did not request this code, please ignore this email or contact
                    your system administrator immediately.</p>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <p><strong>Security Tips:</strong></p>
                <ul style="margin-top: 10px; padding-left: 20px;">
                    <li>Never share your OTP with anyone</li>
                    <li>Our team will never ask for your verification code</li>
                    <li>This code expires in 10 minutes</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated message from your ERP System.</p>
            <p style="margin-top: 8px;">Need help? Contact <a
                    href="mailto:support@yourcompany.com">support@yourcompany.com</a></p>
            <p style="margin-top: 15px; font-size: 12px; color: #aaaaaa;">
                © {{ date('Y') }} - Safe Solution Consultant. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>
