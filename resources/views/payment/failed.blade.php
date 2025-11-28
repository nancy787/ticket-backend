<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>FitPass</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" class="rounded-circle" href="{{ asset('FitPassket_favicon/favicon.ico') }}" type="image/x-icon">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Roboto', sans-serif;
            color: #fff;
            background-color: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Header with logo on the left */
        header {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .logo img {
            height: 40px;
        }

        .container {
        background-color: rgba(0, 0, 0, 0.8); /* Transparent black */
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.5);
        text-align: center;
        max-width: 400px;
        width: 100%;
        color: white;
    }

    h1 {
        color: #ff0000; /* Bright yellow */
        margin-bottom: 1rem;
    }

    p {
        color: #ff0000; /* Bright yellow */
        line-height: 1.6;
    }
            a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        a:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 80px;
            }

            p {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 60px;
            }

            p {
                font-size: 16px;
            }

            a {
                font-size: 14px;
            }
        }
        .nav-highlight {
            background-color: #FFD700;
            color: #000;
        }
        .nav-highlight:hover {
            background-color: #FFA500;
            cursor: pointer;
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
    <header>
        <div class="logo">
            <img src="" alt="Logo">
        </div>
    </header>
    <div class="container">
    <div class="icon">❌</div> <!-- Updated icon -->
    <h1>Failed!</h1>
    <p>Payment Failed</p>
    <div class="footer">
        &copy; {{ date('Y') }} FITPASS. All rights reserved.
    </div>
</div>
</body>
</html>
