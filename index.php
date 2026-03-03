<?php
session_start();

// Basic Session Security headers to prevent clickjacking and XSS reading sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

date_default_timezone_set("Asia/Manila");
include 'config.php';

if (isset($_POST['submit'])) {
    // 1. Sanitize input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $lockoutTime = 10 * 60; // 10 minutes
    $maxLoginAttempts = 3;

    $currentTime = time();
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // 2. Check Login Attempts using PREPARED STATEMENTS
    $stmt = $conn->prepare("SELECT last_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ipAddress);
    $stmt->execute();
    $result = $stmt->get_result();

    $failedAttemptsCount = $result->num_rows;

    if ($failedAttemptsCount >= $maxLoginAttempts) {
        $lastAttemptTime = 0;
        while ($row = $result->fetch_assoc()) {
            $lastAttemptTime = max($lastAttemptTime, $row['last_attempt']);
        }

        $remainingLockoutTime = $lastAttemptTime + $lockoutTime - $currentTime;

        if ($remainingLockoutTime > 0) {
            $minutesRemaining = ceil($remainingLockoutTime / 60);
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: "Unable to Login!",
                        text: "Too many failed attempts. Try again after ' . $minutesRemaining . ' minutes!",
                        icon: "error",
                        confirmButtonText: "Okay",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                });
            </script>';
            $stmt->close();
            // Stop execution so the form doesn't process further
            exit;
        } else {
            // Lockout expired, clear old attempts
            $delStmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $delStmt->bind_param("s", $ipAddress);
            $delStmt->execute();
            $delStmt->close();
        }
    }
    $stmt->close();

    // 3. Fetch User Data using PREPARED STATEMENTS
    $stmt = $conn->prepare("SELECT userid, usertype, password FROM user_tbl WHERE username = ? AND useractive = '1'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $loginFailed = false;

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // SUCCESSFUL LOGIN

            // Critical Security Step: Prevent Session Fixation
            session_regenerate_id(true);

            // Clear login attempts for this IP
            $delStmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $delStmt->bind_param("s", $ipAddress);
            $delStmt->execute();
            $delStmt->close();

            // Log the login
            $loginTime = date("Y-m-d H:i:s");
            $logStmt = $conn->prepare("INSERT INTO userlogs_tbl (userid, username, login_time, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("ssss", $row['usertype'], $username, $loginTime, $ipAddress);
            $logStmt->execute();
            $logStmt->close();

            // Set Session Variables
            $_SESSION['usertype'] = $row['usertype'];
            $_SESSION['userid'] = $row['userid'];
            $_SESSION['logged_in'] = true;

            // Define destination based on usertype
            $redirectUrl = ($row['usertype'] === '1') ? "./user/admin/" : "./user/dashboard/";

            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener("mouseenter", Swal.stopTimer)
                            toast.addEventListener("mouseleave", Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: "success",
                        title: "Signed In Successfully!!!"
                    });

                    setTimeout(function () {
                        window.location.href = "' . $redirectUrl . '";
                    }, 2000);
                });
            </script>';
        } else {
            $loginFailed = true; // Wrong Password
        }
    } else {
        $loginFailed = true; // Wrong Username or Inactive
    }
    $stmt->close();

    // 4. Handle Failed Login
    if ($loginFailed) {
        $failStmt = $conn->prepare("INSERT INTO login_attempts (ip_address, last_attempt) VALUES (?, ?)");
        // 's' for string (IP), 'i' for integer (timestamp)
        $failStmt->bind_param("si", $ipAddress, $currentTime);
        $failStmt->execute();
        $failStmt->close();

        // Generic error message to prevent User Enumeration
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    title: "Login Failed!",
                    text: "Invalid username or password.",
                    icon: "warning",
                    confirmButtonText: "Okay",
                });
            });
        </script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Login - BeachFront Resort</title>
    <link href="uploads/solo_logo.jpg" rel="icon">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            -webkit-tap-highlight-color: transparent;
            /* Tanggalin ang blue highlight sa mobile taps */
        }

        body {
            /* Changed to min-height para hindi masira pag labas ng mobile keyboard */
            min-height: 100vh;
            background-image: linear-gradient(rgba(3, 4, 94, 0.5), rgba(0, 0, 0, 0.8)), url('uploads/polo.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        /* Glassmorphism Card Style */
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 420px;
            border-radius: 24px;
            padding: 70px 30px 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            margin-top: 50px;
            animation: fadeInUp 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overlapping Logo */
        .logo-container {
            position: absolute;
            top: -55px;
            left: 50%;
            transform: translateX(-50%);
            width: 110px;
            height: 110px;
            background: #fff;
            border-radius: 50%;
            padding: 5px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .title {
            color: #ffffff;
            font-size: 1.7rem;
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .subtitle {
            color: #e0e0e0;
            font-size: 0.9rem;
            margin-bottom: 35px;
            font-weight: 300;
        }

        /* Input Groups */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i.prefix-icon {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            /* Larger padding for mobile touch */
            border: 2px solid transparent;
            border-radius: 14px;
            font-size: 16px;
            /* Exactly 16px to prevent iOS Safari auto-zoom */
            color: #333;
            outline: none;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: #ffffff;
            border-color: #00b4d8;
            box-shadow: 0 0 15px rgba(0, 180, 216, 0.3);
        }

        .input-group input:focus+i.prefix-icon,
        .input-group input:not(:placeholder-shown)+i.prefix-icon {
            color: #03045e;
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.1rem;
            transition: 0.3s;
            padding: 5px;
            /* Madaling ma-tap sa mobile */
        }

        .toggle-password:hover {
            color: #03045e;
        }

        /* Warnings */
        #capsLockWarning {
            display: none;
            color: #856404;
            background-color: #fff3cd;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            animation: fadeIn 0.3s;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            border: none;
            background: #03045e;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(3, 4, 94, 0.3);
        }

        .btn-login:hover {
            background: #023e8a;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(3, 4, 94, 0.4);
        }

        .btn-login:active {
            transform: scale(0.97);
        }

        .copyright {
            margin-top: 30px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* MOBILE SPECIFIC STYLING (App-like Bottom Sheet style) */
        @media (max-width: 576px) {
            .login-wrapper {
                align-items: flex-end;
                /* I-push pababa parang bottom sheet */
                padding: 0;
            }

            .login-card {
                max-width: 100%;
                border-radius: 35px 35px 0 0;
                /* Rounded top corners lang */
                margin-top: 120px;
                padding: 60px 25px 30px 25px;
                border-bottom: none;
                border-left: none;
                border-right: none;
                background: rgba(25, 25, 25, 0.65);
                /* Darker contrast para mabasa sa phone outdoors */
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
            }

            .logo-container {
                width: 90px;
                height: 90px;
                top: -45px;
            }

            .title {
                font-size: 1.5rem;
            }

            .btn-login {
                padding: 18px;
                font-size: 1rem;
            }

            /* Mas malaking touch target */
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <div class="login-card">

            <div class="logo-container">
                <img src="uploads/solo_logo.jpg" class="logo-img" alt="Logo"
                    onerror="this.src='https://cdn-icons-png.flaticon.com/512/2983/2983748.png'">
            </div>

            <h2 class="title">WELCOME BACK</h2>
            <p class="subtitle">Sign in to BeachFront Resort</p>

            <form method="POST" autocomplete="off" id="loginForm">

                <div class="input-group">
                    <input type="text" name="username" id="username" placeholder="Username" required>
                    <i class="fas fa-user prefix-icon"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-lock prefix-icon"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <div id="capsLockWarning">
                    <i class="fas fa-exclamation-triangle me-1"></i> Caps Lock is ON!
                </div>

                <input type="submit" name="submit" value="LOGIN" class="btn-login" id="loginBtn">
            </form>

            <div class="copyright">
                &copy; <?= date('Y') ?> BeachFront Resort.<br>All rights reserved.
            </div>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Caps Lock Detection
        const passwordInput = document.getElementById("password");
        const capsLockWarning = document.getElementById("capsLockWarning");

        passwordInput.addEventListener("keyup", function (event) {
            if (event.getModifierState("CapsLock")) {
                capsLockWarning.style.display = "block";
            } else {
                capsLockWarning.style.display = "none";
            }
        });

        // Button Loading Animation
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            const width = btn.offsetWidth;
            btn.style.width = width + 'px'; // Prevent resizing
            btn.value = 'LOGGING IN...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none'; // Prevent double clicking on slow connections
        });
    </script>

</body>

</html>