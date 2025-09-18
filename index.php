<?php
// login_options.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Options - Cricket Auction System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('assets/img/cricket-bg.jpg') center center/cover no-repeat fixed, linear-gradient(135deg, #e3eafc 0%, #fff 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
        }
        .login-options {
            position: absolute;
            top: 40px;
            left: 40px;
            text-align: left;
        }
        .login-title {
            color: #1976d2;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }
        .login-title {
            color: #fff;
        }
        .login-links {
            font-size: 1.3rem;
            margin: 2rem 0 0 0;
        }
        .login-links a {
            text-decoration: none;
            font-weight: 600;
        @media (max-width: 600px) {
            .login-options {
                position: static;
            margin-right: 2rem;
            transition: color 0.2s;
        }
        .login-links a.admin {
            color: #fff;
        <div class="login-title" style="text-align:center;">Login As</div>
        .login-links a.team {
            color: #fff;
        }
        @media (max-width: 600px) {
            .login-options {
                position: static;
                margin: 32px 0 0 0;
                text-align: center;
                width: 100%;
            }
            .login-title {
                font-size: 1.4rem;
                text-align: center;
            }
            .login-links {
                font-size: 1.05rem;
                margin-top: 1.2rem;
                text-align: center;
                width: 100%;
            }
            .login-links a {
                margin-right: 1rem;
                display: inline-block;
            }
        }
    </style>
</head>
<body>
    <div class="login-options">
        <div class="login-title">Login As</div>
        <div class="login-links">
            <a href="login.php" class="admin btn btn-primary">Admin Login</a>
            <a href="team" class="team btn btn-primary">Team Login</a>
        </div>
    </div>
</body>
</html>
