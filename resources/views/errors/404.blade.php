<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>FitPass</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" class="rounded-circle" href="" type="image/x-icon">
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

        /* Main container */
        .container {
            background-color: #333;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            font-size: 100px;
            margin: 0;
            color: #fff;
        }

        p {
            font-size: 20px;
            color: #fff;
            margin-bottom: 30px;
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
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="" alt="Logo"> <!-- Replace with the logo -->
        </div>
    </header>

    <div class="container">
        <h1>404</h1>
        <p>Oops! The page you're looking for couldn't be found.</p>
        <a href="{{ route('login') }}" class="nav-highlight">Back to Login</a>
    </div>
</body>
</html>
