<?php
include "config.php";
session_start();

// Debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If already logged in
if (isset($_SESSION['user_id'])) {
    echo "<script>alert('You are already logged in.'); window.location.href='home.php';</script>";
    exit();
}

// Load PHPMailer
require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle login form
if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['pwd']);
    $role_input = strtolower($_POST['role'] ?? '');
    $admin_code = trim($_POST['admin_code'] ?? '');

    // Fetch user
    $stmt = mysqli_prepare($conn, "SELECT * FROM credentials WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        // ✅ Check account status before anything else
        if ($row['status'] === 'suspended') {
            echo "<script>alert('Your account has been suspended by the admin.'); window.location.href='login.html';</script>";
            exit();
        }
        // Allow pending and active users to log in
        elseif ($row['status'] !== 'active' && $row['status'] !== 'pending') {
            echo "<script>alert('Your account status is invalid. Please contact support.'); window.location.href='login.html';</script>";
            exit();
        }

        // Verify password
        if (password_verify($password, $row['pwd'])) {

            // Role validation
            $user_role = strtolower($row['role']);
            if ($role_input === 'admin') {
                if ($user_role !== 'admin') {
                    echo "<script>alert('This account is not an admin.'); window.location.href='login.html';</script>";
                    exit();
                }

                $correct_admin_code = "SECRET123";
                if ($admin_code !== $correct_admin_code) {
                    echo "<script>alert('Invalid admin code.'); window.location.href='login.html';</script>";
                    exit();
                }
            }

            // Generate OTP
            $otp = rand(100000, 999999);
            $expires = date("Y-m-d H:i:s", strtotime("+2 minutes"));

            // Save OTP in DB
            $update = mysqli_prepare($conn, "UPDATE credentials SET otp_code=?, otp_expires=? WHERE email=?");
            mysqli_stmt_bind_param($update, "sss", $otp, $expires, $email);
            mysqli_stmt_execute($update);

            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'vivekkrishna960@gmail.com'; // sender
                $mail->Password   = 'vspsxiaiqtoefxgb';          // app password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom('vivekkrishna960@gmail.com', 'NoCapPress');
                $mail->addAddress($email, $row['uname']);

                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for NoCapPress';
                $mail->Body    = "Hello {$row['uname']},<br>Your OTP is <b>$otp</b>. It expires in <b>2 minutes</b>.";

                $mail->send();

                // Save pending login info in session
                $_SESSION['pending_email']  = $email;
                $_SESSION['pending_role']   = $role_input;
                $_SESSION['otp_expires']    = strtotime($expires);

                header("Location: verify_otp.php");
                exit();

            } catch (Exception $e) {
                echo "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
                exit();
            }

        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='login.html';</script>";
            exit();
        }

    } else {
        echo "<script>alert('Email not found.'); window.location.href='login.html';</script>";
        exit();
    }
}
?>
