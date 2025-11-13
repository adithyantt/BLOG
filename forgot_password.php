<?php
session_start();
include "config.php";

// Load PHPMailer
require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your registered email.";
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT user_id, uname FROM credentials WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $uname = $row['uname'];

            // Generate OTP
            $otp = rand(100000, 999999);
            $expires = date("Y-m-d H:i:s", strtotime("+2 minutes"));

            // Save OTP in DB
            $stmt = mysqli_prepare($conn, "UPDATE credentials SET otp_code=?, otp_expires=? WHERE email=?");
            mysqli_stmt_bind_param($stmt, "sss", $otp, $expires, $email);
            mysqli_stmt_execute($stmt);

            // Send OTP via email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'vivekkrishna960@gmail.com'; // sender email
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
                $mail->addAddress($email, $uname);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP - NoCapPress';
                $mail->Body    = "Hello $uname,<br>Your password reset OTP is <b>$otp</b>. It expires in <b>2 minutes</b>.";

                $mail->send();

                // Save reset session
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp_expires'] = strtotime($expires);

                header("Location: reset_otp.php");
                exit();
            } catch (Exception $e) {
                $error = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "No account found with this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - NoCapPress</title>
<style>
body { font-family: Arial, sans-serif; background: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
.container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
h2 { text-align: center; margin-bottom: 20px; }
input[type="email"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #007BFF; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #0056b3; }
.error { color: red; text-align: center; margin-bottom: 10px; }
.success { color: green; text-align: center; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <?php if($success) echo "<div class='success'>$success</div>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit">Send OTP</button>
    </form>
</div>
</body>
</html>
