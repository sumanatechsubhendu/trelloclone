<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{$site_name}}</title>
    <style>
        /* Add your email-specific CSS styles here */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .content {
            padding: 20px;
        }
        .footer {
            text-align: center;
            background-color: #f5f5f5;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logo }}" alt="{{$site_name}}" class="logo">
        </div>
        <div class="content">
            <p> Dear {{ $email}}, </p>
            <p>  You have successfully updated your password.</p>

<br/>
            <p> Warmly, </p>
            <p> Trello Clone Apps </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{$site_name}} | All Rights Reserved</p>
        </div>
    </div>
</body>
</html>
