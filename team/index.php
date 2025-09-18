<?php
// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cricket_auction');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is already logged in via session
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'team') {
    // Redirect to dashboard
    header("Location: team-dashboard.php");
    exit();
}

// Check for remember me cookie
if (isset($_POST['login'])) {

    $username = $_POST['username'];
    
    // echo "hello"; die();

    $stmt = $conn->prepare("SELECT * FROM teams WHERE  name = ? AND role = 'team'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // print_r($user); die;
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['team_id'] = $user['id'];
        
        // Update last login time

        
        // Redirect to team dashboard
        header("Location:dashboard.php");
        exit();
    }
    
    $stmt->close();
}

$conn->close();

// If not logged in, show the login form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Login - Cricket Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4caf50;
            --danger-color: #f44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(67, 97, 238, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(76, 201, 240, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(63, 55, 201, 0.03) 0%, transparent 70%);
            z-index: -1;
        }
        
        .cricket-element {
            position: absolute;
            opacity: 0.05;
            z-index: -1;
        }
        
        .cricket-element.bat {
            top: 10%;
            right: 5%;
            font-size: 120px;
            transform: rotate(45deg);
        }
        
        .cricket-element.ball {
            bottom: 10%;
            left: 5%;
            font-size: 80px;
        }
        
        .cricket-element.stumps {
            top: 20%;
            left: 10%;
            font-size: 100px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            position: relative;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .login-header h1 {
            color: white;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            background-color: white;
        }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e0e6ed;
            border-right: none;
            color: #6c757d;
            padding: 14px 18px;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-left: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        .form-check {
            margin-bottom: 25px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 1px solid #ced4da;
            margin-top: 0.25em;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        .login-footer {
            text-align: center;
            padding: 25px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .login-footer p {
            margin-bottom: 5px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
        }
        
        .toggle-password {
            background-color: transparent;
            border: none;
            color: #6c757d;
            padding: 0 15px;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        .logo-container {
            width: 70px;
            height: 70px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container i {
            font-size: 36px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    
    <!-- Cricket elements -->
    <div class="cricket-element bat">
        <i class="bi bi-baseball"></i>
    </div>
    <div class="cricket-element ball">
        <i class="bi bi-circle"></i>
    </div>
    <div class="cricket-element stumps">
        <i class="bi bi-bar-chart"></i>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1>Team Login</h1>
            <p>Cricket Auction Portal</p>
        </div>
        
        <div class="login-body">
            <!-- Error Message (hidden by default) -->
            <div class="alert alert-danger d-none" id="errorAlert" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span id="errorMessage">Invalid username or password</span>
            </div>
            
            <form id="loginForm" method="post">
                <div class="form-group">
                    <label for="username" class="form-label">Team Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your team username" required>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login" name="login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login to Team Dashboard
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('bi-eye');
                passwordIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('bi-eye-slash');
                passwordIcon.classList.add('bi-eye');
            }
        });
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                event.preventDefault();
                showError('Please enter both username and password');
            }
        });
        
        // Show error message
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            
            // Hide error after 5 seconds
            setTimeout(() => {
                errorAlert.classList.add('d-none');
            }, 5000);
        }
        
        // Check for URL parameters to show error messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === '1') {
            showError('Invalid username or password. Please try again.');
        } else if (urlParams.get('error') === '2') {
            showError('Your session has expired. Please login again.');
        }
    </script>
</body>
</html>