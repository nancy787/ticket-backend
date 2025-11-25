<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Onboarding Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<style>
      header {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .logo img {
            height: 40px;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

</style>
<body class="bg-dark text-light">
<header>
    <div class="logo">
        <img src="" alt="Logo">
    </div>
</header>
    <div class="container d-flex flex-column align-items-center justify-content-center vh-100">
        <div class="text-center">
            <h1 class="text-warning">Congratulations!</h1>
            <p class="lead">
                Your Stripe account has been successfully linked. You can now accept payments and receive payouts.
            </p>
            <p class="mt-3 text-muted">
                Need assistance? <a href="/help" class="text-decoration-underline text-info mt-3">Contact Support</a>
            </p>
        </div>
    </div>
      <div class="footer">
                &copy; {{ date('Y') }} Rox Tickets. All rights reserved.
            </div>
</body>
</html>
