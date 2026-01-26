<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message from Florence with Locals</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Florence with Locals</h1>
    </div>

    <div class="content">
        {!! nl2br(e($content)) !!}
    </div>

    <div class="footer">
        <p class="logo">Florence with Locals</p>
        <p>
            Need help? Contact us at<br>
            <a href="mailto:info@florencewithlocals.com">info@florencewithlocals.com</a>
        </p>
    </div>
</body>
</html>
