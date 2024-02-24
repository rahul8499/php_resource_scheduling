<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email with Image</title>
    <style>
        /* Style for the image */
        .logo {
            width: 100px; /* Set width to desired size */
            position: relative; /* Position relatively */
            margin-top: 20px; /* Add space between image and content */
            margin-left: 20px; /* Add space from left side */
        }

        /* Style for the email body */
        body {
            margin-top: 20px; /* Add space from top */
            margin-left: 20px; /* Add space from left side */
        }

        /* Additional styles for clarity */
        table {
            margin-top: 20px; /* Add margin between image and table */
        }
    </style>
</head>
<body>
<img src="https://allen-storage1.s3.ap-south-1.amazonaws.com/faculty_images/lN647q3W9G61RlnnrVQ1jxzI4RxRqgDTAwjnK0iG.jpg" alt="Your Image" class="logo" />
<p>Hello {{ $facultyName }},</p>

<p>Your schedules have been published.</p>

<table border="1">
    <thead style="background-color: #006400; color: white;">
    <tr>
        <th style="padding: 10px; text-align: center;">Date</th>
        <th style="padding: 10px; text-align: center;">Batch Code</th>
        <th style="padding: 10px; text-align: center;">Slot Time</th>
        <th style="padding: 10px; text-align: center;">Location</th>
        <th style="padding: 10px; text-align: center;">Subject</th>
    </tr>
</thead>
    <tbody style="background-color: lightgrey; text-align: center;">
        @foreach ($schedules as $schedule)
        <tr>
            <td>{{ $schedule['date'] }}</td>
            <td>{{ $schedule['batch_code'] }}</td>
            <td>{{ $schedule['slot_time'] }}</td>
            <td>{{ $locationName }}</td>
            <td>{{ $schedule['subject_name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p>Thank you for your dedication to Allen Institute!</p>
<hr> <!-- Add a separator between faculty emails -->

</body>
</html>
