<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Success</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: black;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    color: white;
    background-image: url('/path/to/your/background/image.jpg');
    background-size: cover;
    background-position: center;
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
    color: #FFD700; /* Bright yellow */
    margin-bottom: 1rem;
}

p {
    color: #FFD700; /* Bright yellow */
    line-height: 1.6;
}

.icon {
    font-size: 48px;
    margin-bottom: 1rem;
    color: #FFD700; /* Bright yellow */
}

#countdown {
    font-weight: bold;
    color: #FFD700; /* Bright yellow */
}

a.button {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background-color: #FFD700;
    color: black;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

a.button:hover {
    background-color: #FFA500; /* Orange */
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
    <div class="container">
        <div class="icon">✅</div>
        <h1>Success!</h1>
        @if (session('status'))
            <p>{{ session('status') }}</p>
        @else
            <p>Your password has been reset successfully.</p>
        @endif
        <p>Redirecting to login page in <span id="countdown">5</span> seconds...</p>

        <a href="{{ env('APP_LOGIN_URL') }}" class="button">Go to Login</a>
        <div class="footer">
                &copy; {{ date('Y') }} FITPASS. All rights reserved.
            </div>
    </div>

    <script>
        const appLoginUrl = "{{ env('APP_LOGIN_URL') }}";
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdownTimer = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(countdownTimer);
                window.location.href = appLoginUrl;
            }
        }, 1000);
    </script>
</body>
</html>