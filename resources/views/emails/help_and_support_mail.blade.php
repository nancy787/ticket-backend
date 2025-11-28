<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help And Support</title>
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
            <h1>Help and support</h1>
            <p class="highlight">Hello, {{ env('MAIL_USERNAME') }}</p>
            <p class="highlight">{{ $helpAndSupport->name }} ({{$helpAndSupport->email }}) has send a query.</p>
            <p><span class="highlight">Subject : </span> {{ $helpAndSupport->subject }}</p>
            <p><span class="highlight">Message : </span> {{ $helpAndSupport->message }}</p>
            <div class="footer">
                &copy; {{ date('Y') }} FITPASS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
