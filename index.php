<?php
session_start();
date_default_timezone_set("Asia/Manila");
include 'config.php';

if (isset($_POST['submit'])) {
    // ... (Keep existing PHP logic exactly as is) ...
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $lockoutTime = 10 * 60;
    $maxLoginAttempts = 3;

    $currentTime = time();
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $sql = "SELECT * FROM login_attempts WHERE ip_address = '$ipAddress'";
    $result = mysqli_query($conn, $sql);

    $failedAttemptsCount = mysqli_num_rows($result);

    if ($failedAttemptsCount >= $maxLoginAttempts) {
        $lastAttemptTime = mysqli_fetch_assoc($result)['last_attempt'];
        $remainingLockoutTime = $lastAttemptTime + $lockoutTime - $currentTime;

        if ($remainingLockoutTime > 0) {
            $minutesRemaining = ceil($remainingLockoutTime / 60);
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        title: "Unable to Login!",
                        text: "Try Again After ' . $minutesRemaining . ' minutes!",
                        icon: "error",
                        confirmButtonText: "Okay",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                });
            </script>';
        } else {
            $sql = "DELETE FROM login_attempts WHERE ip_address = '$ipAddress'";
            mysqli_query($conn, $sql);
        }
    } else {
        $sql = "SELECT * FROM user_tbl WHERE BINARY username = '$username' AND useractive = '1'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            $usertype = $row['usertype'];

            if (password_verify($password, $row['password'])) {
                $sql = "DELETE FROM login_attempts WHERE ip_address = '$ipAddress'";
                mysqli_query($conn, $sql);

                $loginTime = date("Y-m-d H:i:s");
                $sql = "INSERT INTO userlogs_tbl (userid, username, login_time, ip_address) VALUES ('$usertype', '$username', '$loginTime', '$ipAddress')";
                mysqli_query($conn, $sql);
                $encodedUrl = base64_encode("./user/admin/");
                if ($row['usertype'] === '1') {

                    $_SESSION['usertype'] = 1;
                    $_SESSION['userid'] = $row['userid'];
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
                                window.location.href = atob("' . $encodedUrl . '");
                            }, 2000);
                        });
                    </script>';
                } else {
                    // Unknown user type handler if needed
                }
            } else {
                $sql = "INSERT INTO login_attempts (ip_address, last_attempt) VALUES ('$ipAddress', $currentTime)";
                mysqli_query($conn, $sql);

                echo '<script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({
                            title: "Incorrect Credential!",
                            text: "Please check your username or password.",
                            icon: "warning",
                            confirmButtonText: "Okay",
                        });
                    });
                </script>';
            }
        } else {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    title: "Incorrect Credential!",
                    text: "Account not found or inactive.",
                    icon: "warning",
                    confirmButtonText: "Okay",
                });
            });
        </script>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - BeachFront Resort</title>
    <link href="uploads/solo_logo.jpg" rel="icon">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-image: linear-gradient(rgba(13, 10, 161, 0.6), rgba(0, 0, 0, 0.8)), url('uploads/polo.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            overflow: hidden;
        }

        /* Dark Overlay para mas mabasa ang login box */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Main Card Style - Ginaya yung nasa picture (Solid White) */
        .login-card {
            background: rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            /* Saktong lapad lang */
            border-radius: 20px;
            padding: 60px 35px 40px 35px;
            /* Extra padding sa top para sa logo */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            margin-top: 40px;
            /* Space para sa logo overlap */
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo Container - Overlapping Effect */
        .logo-container {
            position: absolute;
            top: -60px;
            /* Hatakin pataas para mag-overlap */
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            background: #fff;
            border-radius: 50%;
            padding: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            color: white;
            /* Dark Blue Theme */
            font-size: 1.6rem;
            font-weight: 800;
            margin-top: 40px;
            /* Space mula sa logo */
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: #777;
            font-size: 0.85rem;
            margin-bottom: 30px;
            font-weight: 400;
        }

        /* Input Groups Styling */
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        /* Icon Design */
        .input-group i.prefix-icon {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: #03045e;
            /* Brand Color */
            font-size: 1.1rem;
            z-index: 2;
            transition: 0.3s;
        }

        /* Input Field Design */
        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 50px;
            /* Space para sa icon */
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            /* Rounded corners like the image */
            font-size: 0.95rem;
            color: #333;
            outline: none;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #03045e;
            background: #fff;
            box-shadow: 0 3px 10px rgba(3, 4, 94, 0.1);
        }

        .input-group input::placeholder {
            color: #aaa;
        }

        /* Toggle Password Eye */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            transition: 0.3s;
        }

        .toggle-password:hover {
            color: #03045e;
        }

        /* Warning Box */
        #capsLockWarning {
            display: none;
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            text-align: center;
            animation: fadeIn 0.3s;
        }

        /* Login Button - Solid Blue Rounded */
        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 50px;
            /* Fully rounded pill shape */
            border: none;
            background: #03045e;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(3, 4, 94, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #023e8a;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(3, 4, 94, 0.4);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .copyright {
            margin-top: 30px;
            font-size: 0.75rem;
            color: #888;
        }

        /* Responsive Adjustments */
        @media (max-width: 480px) {
            .login-card {
                padding: 50px 25px 30px 25px;
            }

            .title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body> <br>

    <div class="login-wrapper">

        <div class="login-card">

            <!-- Overlapping Logo -->
            <div class="logo-container">
                <img src="uploads/solo_logo.jpg" class="logo-img" alt="Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2983/2983748.png'">
            </div>

            <!-- Titles -->
            <h2 class="title">WELCOME BACK</h2>
            <p class="subtitle">Sign in to BeachFront Reservation System</p>

            <form method="POST" autocomplete="off" id="loginForm">

                <!-- Username -->
                <div class="input-group">
                    <i class="fas fa-user prefix-icon"></i>
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <i class="fas fa-lock prefix-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <!-- Caps Lock Warning -->
                <div id="capsLockWarning">
                    <i class="fas fa-exclamation-triangle me-1"></i> Caps Lock is ON!
                </div>

                <!-- Submit Button -->
                <input type="submit" name="submit" value="LOGIN" class="btn-login" id="loginBtn">
            </form>

            <div class="copyright">
                &copy; <?= date('Y') ?> BeachFront Resort. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');

            password.focus();
        });

        // Caps Lock Detection
        const passwordInput = document.getElementById("password");
        const capsLockWarning = document.getElementById("capsLockWarning");

        passwordInput.addEventListener("keyup", function(event) {
            if (event.getModifierState("CapsLock")) {
                capsLockWarning.style.display = "block";
            } else {
                capsLockWarning.style.display = "none";
            }
        });

        // Button Loading Animation
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const width = btn.offsetWidth;
            btn.style.width = width + 'px'; // Prevent resizing
            btn.value = '...';
            btn.style.opacity = '0.8';
            btn.style.cursor = 'wait';
        });
    </script>

</body>

</html>