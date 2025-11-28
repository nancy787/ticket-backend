<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Purchsed</title>
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
        ul {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        li {
            font-size: 16px;
            margin-bottom: 10px;
        }
        strong {
            color: #fff;
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
            <h1>Payment Receipt</h1>
            <p>Dear <span class="highlight">{{ $buyerName }}</span>,</p>
            <p>You have bought purchased <span class="highlight">#{{ $archiveTickets->ticket_id }} </span> for <span class="highlight">{{ $sellTicket->event->name }} </span></p>
            <p>The ticket is now on your app.Please personalise the ticket and change the names ASAP.
                Please make sure you have read the event organisers Terms and Conditions around tickets. <a class="highlight" href="https://FitPass.app/terms-and-condition">https://FitPass.app/terms-and-condition</a>
                Please make yourself aware of deadlines for ticket name changes online as per the terms. We suggest you make the changes without delay.
                Thanks for using FitPass
            </p>
            <p>Trust the process</p>
            <p>Thank you for using our service!</p>
            <ul>
                <li><strong>Transaction ID:</strong> <span class="highlight">#{{ $transaction->transaction_id }}</span></li>
                <li><strong>Amount:</strong> <span class="highlight">{{ $archiveTickets->currency_type ?? '' }}{{ format_price($transaction->amount) }}</span></li>
                <li><strong>Date:</strong> <span class="highlight">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span></li>
            </ul>
            <div class="footer">
                &copy; {{ date('Y') }} FITPASS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
