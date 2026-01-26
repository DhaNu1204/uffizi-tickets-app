<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Uffizi Gallery Tickets</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #c4a000;
        }
        .header h1 {
            color: #8b6914;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 0;
            white-space: pre-line;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .logo {
            font-weight: bold;
            color: #8b6914;
        }
        .highlight {
            background-color: #fff9e6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .attachment-note {
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎟️ Your Uffizi Gallery Tickets</h1>
    </div>

    <div class="content">
        {!! nl2br(e($content)) !!}
    </div>

    <div class="attachment-note">
        📎 Your tickets are attached to this email as a PDF file. Please show them at the museum entrance.
    </div>

    <div class="footer">
        <p class="logo">Florence with Locals</p>
        <p>
            Need help? Contact us at<br>
            <a href="mailto:info@florencewithlocals.com">info@florencewithlocals.com</a>
        </p>
        <p style="font-size: 12px; color: #999;">
            This is an automated message. Please do not reply directly to this email.
        </p>
    </div>
</body>
</html>
