<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Email</title>
    <style>
        /* Style for the image */
        .logo {
            width: 100px; /* Set width to desired size */
            margin-bottom: 20px; /* Add space between image and content */
        }
        /* Style for the main content */
        .content {
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        /* Style for the link */
        .reset-link {
            color: #007bff; /* Set link color */
            text-decoration: none; /* Remove underline */
        }
    </style>
</head>
<body class="content">

<img src="https://allen-storage1.s3.ap-south-1.amazonaws.com/faculty_images/lN647q3W9G61RlnnrVQ1jxzI4RxRqgDTAwjnK0iG.jpg" alt="Allen Logo" class="logo" />

<p>Hello {{ $user['name'] }},</p>

<p>We received a request to reset your password. If this wasn't you, please disregard this email.</p>

<p>To reset your password, click the link below:</p>

<p><a href="{{ $resetLink }}">{{ $resetLink }}</a></p>

<p>Thank you for connecting to us.</p>

<p>Best regards,<br>The Allen Team</p>

</body>
</html>
