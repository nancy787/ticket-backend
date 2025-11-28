<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expires</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #000;
            color: #fff;
        }
        .email-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1a1a1a;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
        }
        h4 {
            font-size: 24px;
            color: #fff;
            text-align: center;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            color: #d1d1d1;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .highlight {
            color: #ffcc00;
            font-weight: bold;
        }
        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #ffcc00;
            color: #000;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-content">
            <h4>Subscription Expires</h4>
            <p>Dear <span class="highlight">{{ $userName }}</span>,</p>
            <p>Your subscription is due to expire on <span class="highlight">{{ $subscriptionExpiresDate }}</span>. To continue using your wishlist, please renew your subscription.</p>
            
            <div class="button-container">
                <a href="#" class="button">Renew Subscription</a>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} FITPASS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
