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
            overflow-y: scroll;
        }
        body::-webkit-scrollbar {
            display: none; 
        }
        body {
            -ms-overflow-style: none;
        }
        body {
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
            height: calc(100vh - 80px);
        }
        .image-section {
            flex: 1;
            background-image: url('bannerImages/FitPassket-bg.jpeg');
            background-size: cover;
            background-position: center;
            opacity: 0.8;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-section img {
            width: 80%;
            margin: auto;
            display: block;
        }
        .contact-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background-color: #222;
        }
        .contact-section h2 {
            text-align: center;
            color: #FFD700;
            margin-bottom: 30px;
        }
        .contact-form {
            display: flex;
            flex-direction: column;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            color: #fff;
        }
        .contact-form input, .contact-form textarea {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        .contact-form button {
            background-color: #FFD700;
            color: #000;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .contact-form button:hover {
            background-color: #FFA500;
        }
        @media (max-width: 1024px) {
            .main-content {
                flex-direction: column;
            }
            .image-section, .contact-section {
                height: 50vh;
            }
        }
        @media (max-width: 768px) {
            .contact-form input, .contact-form textarea {
                padding: 12px;
            }
        }
        @media (max-width: 480px) {
            .contact-section h2 {
                font-size: 1.5rem;
            }
        }
        .custom-success-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #FFD700;
            color: #000;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .show-message {
            opacity: 1;
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
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="" alt=" Logo">
        </div>
        <nav>
            <a href="{{ route('login') }}" class="nav-highlight">Login as admin</a>
        </nav>
    </header>
    <div id="successMessage"></div>
    <div class="main-content">
        <div class="image-section">
        </div>
        <div class="contact-section">
            <h2>Contact Us</h2>
            <form id="contactUs" class="contact-form">
                @csrf
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="email" name="email" placeholder="Your Email" required>
                <input type="text" name="subject" placeholder="Subject" required>
                <textarea name="message" rows="10" placeholder="Your Message" required></textarea>
                <button type="">Send Message</button>
            </form>
        </div>
    </div>
    <section>
        <div>
        </div>
    </section>
</body>
</html>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('#contactUs').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: "POST",
            url: "{{ route('contact-us') }}",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#successMessage').html('<div class="custom-success-message show-message">' + response.success + '</div>');
                setTimeout(function() {
                    $('.custom-success-message').removeClass('show-message').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 2000);
                $('#contactUs')[0].reset();
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                $('.text-danger').empty();
                $.each(errors, function(key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });
    });
});
</script>