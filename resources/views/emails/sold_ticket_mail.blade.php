<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Sold</title>
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
        h1 {
            font-size: 28px;
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
            <h1>Ticket Sold</h1>
            <p>Dear <span class="highlight">{{ $sellerName }}</span>,</p>
            <p>Your ticket <span class="highlight">#{{ $archiveTickets->ticket_id }} </span> for <span class="highlight">{{ $sellTicket->event->name }} </span> has now sold.</p>
            @if($isStripeConnected)
                <p>The funds have ben transferred to your Stripe Connect account and will be sent on to your designated account within 7 days.</p>
            @else
               <p>Please contact support to arrange for payment.</p>
            @endif
            <p>If you have any questions please send us an email on <span class="highlight">admin@FitPass.app</span> or send a support query in app.</p>
            <p>Thank you for using our service!</p>

            <div class="footer">
                &copy; {{ date('Y') }} FITPASS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>