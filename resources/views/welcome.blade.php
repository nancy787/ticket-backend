<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Fitpass</title>
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
            overflow-y: scroll;
        }
        body::-webkit-scrollbar {
            display: none;
        }
        body {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        header {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background-color: #000;
            align-items: center;
        }
        .logo img {
            height: 40px;
        }
        nav {
            display: flex;
            gap: 20px;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: 700;
        }

        .nav-highlight {
            background-color: #FFD700;
            color: #000;
        }
        .nav-highlight:hover {
            background-color: #FFA500;
            cursor: pointer;
        }

        .main-content {
            display: flex;
            flex-wrap: wrap;
            height: calc(100vh - 80px);
        }
        .image-section {
            flex: 1;
            background-image: url('bannerImages/fitticket-bg.jpeg');
            background-size: cover;
            background-position: center;
            min-height: 40vh; /* Adjusted to ensure it doesn't take too much height */
        }
        .text-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #000;
            padding: 20px;
        }
        .yellow-box {
            background-color: #FFD700;
            padding: 20px;
            color: #000;
            font-size: 2.2rem;
            font-weight: bold;
            line-height: 1.2;
            text-align: center;
        }
        .cta-button, .app-button {
            background-color: #FFD700;
            color: #000;
            padding: 15px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .cta-button {
            margin-bottom: 20px;
        }
        .app-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .app-button i {
            margin-right: 10px;
        }
        .app-button:hover {
            background-color: #FFA500;
        }
        .ticket-info {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #fff;
            padding: 20px;
            background-color: #333;
            border-radius: 8px;
            margin: 20px 0;
        }
        .ticket-info .text-highlight {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #FFD700;
            margin-top: 10px;
        }
        footer {
            background-color: #111;
            padding: 20px;
            text-align: center;
            color: #fff;
        }
        footer a {
            color: #FFD700;
            text-decoration: none;
            margin: 0 10px;
        }
        footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .main-content {
                flex-direction: column;
            }
            .image-section {
                min-height: 30vh; /* Reduce height on medium screens */
            }
            .text-section {
                padding: 20px;
            }
            .yellow-box {
                font-size: 1.8rem;
            }
        }
        @media (max-width: 768px) {
            .yellow-box {
                font-size: 1.5rem;
            }
            .cta-button, .app-button {
                padding: 10px 15px;
            }
        }
        @media (max-width: 480px) {
            .image-section {
                min-height: 20vh; /* Further reduce height for small screens */
            }
            .text-section {
                padding: 15px;
            }
            .yellow-box {
                font-size: 1.2rem;
            }
            .app-buttons {
                flex-direction: column;
            }
            nav a {
                font-size: 14px;
            }
            .ticket-info {
                font-size: 1rem;
            }
            footer {
                background-color: #111;
                padding: 20px;
                text-align: center;
                color: #fff;
            }

            footer div {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            footer p {
                margin: 10px 0;
                font-size: 1rem;
            }

            footer a {
                color: #FFD700;
                text-decoration: none;
                margin: 0 10px;
            }

            footer a:hover {
                text-decoration: underline;
            }

            /* Responsive Styles */
            @media (max-width: 768px) {
                footer p {
                    font-size: 0.9rem; /* Reduce footer text size */
                }

                footer div {
                    align-items: center; /* Center-align all elements */
                }

                footer a {
                    display: block;
                    margin: 5px 0; /* Stack links vertically */
                }
            }

            @media (max-width: 480px) {
                footer p {
                    font-size: 0.8rem; /* Further reduce text size for small screens */
                }

                footer a {
                    margin: 5px 0; /* Ensure links are not too close on small screens */
                }
            }

        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src=" " alt=" Logo"> 
        </div>
        <nav>
            <a href="{{ route('login') }}" class="nav-highlight">Login as admin</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="image-section"></div>
        <div class="text-section">
        <p class="ticket-info">fitTickets app is a dedicated platform for buying and selling tickets to fitness races Worldwide. The secure platform allows you to browse for tickets you are looking to buy, list your tickets for sale, and even set up a Wishlist to receive notifications when tickets matching your preferences are added. All tickets listed on the app are verified to ensure their authenticity before being approved for sale.<span class="text-highlight">TRUST THE PROCESS</span></p>
            <div class="app-buttons">
                <a class="app-button" href="{{ env('IOS_APP_URL') }}" target="_blank">
                    <i class="fab fa-apple"></i>Download on iOS</a>
                    <a class="app-button" href="{{ asset('downloads/' . env('APK_FILE_NAME')) }}" target="_blank">
                    <i class="fab fa-android"></i>Download on Android</a>
                <a class="app-button" href="{{ env('AMAZON_STORE') }}" target="_blank">
                <i class="fab fa-store"></i>Download on Amazon</a>
            </div>
        </div>
    </div>

    <section>
        <div>
            <footer>
                <div>
                    <p>&copy; 2024 fitTickets. All rights reserved.</p>
                    <p>Follow us on
                        <a href="https://www.facebook.com/groups/fittickets" target="_blank">Facebook</a> |
                        <a href="https://www.instagram.com/fittickets" target="_blank">Instagram</a>
                    </p>
                    <p>
                        <a href="{{ route('terms-and-condition') }}" target="_blank">Terms of Service</a> |
                        <a href="{{ route('privacy-policy') }}" target="_blank">Privacy Policy</a> |
                        <a href="{{ route('help-and-suppport') }}" target="_blank">Help & Support</a>
                    </p>
                </div>
            </footer>
        </div>
    </section>
</body>
</html>
