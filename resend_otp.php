<?php
session_start();
include "config.php";

// Load PHPMailer
require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if there's a pending email
if (!isset($_SESSION['pending_email'])) {
    header("Location: signup.html");
    exit();
}

$email = $_SESSION['pending_email'];
$role  = isset($_SESSION['pending_role']) ? $_SESSION['pending_role'] : ""; // SAFETY FIX

// Fetch user info
$stmt = mysqli_prepare($conn, "SELECT uname FROM credentials WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $uname = isset($row['uname']) ? $row['uname'] : ''; // SAFETY FIX

    // Generate new OTP
    $otp = rand(100000, 999999);
    $expires = date("Y-m-d H:i:s", strtotime("+2 minutes"));

    // Update OTP in DB
    $stmt = mysqli_prepare($conn, "UPDATE credentials SET otp_code=?, otp_expires=? WHERE email=?");
    mysqli_stmt_bind_param($stmt, "sss", $otp, $expires, $email);
    mysqli_stmt_execute($stmt);

    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vivekkrishna960@gmail.com';
        $mail->Password = 'vspsxiaiqtoefxgb';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('vivekkrishna960@gmail.com', 'NoCapPress');
        $mail->addAddress($email, $uname);

        $mail->isHTML(true);
        $mail->Subject = 'Resent OTP - NoCapPress';
        $mail->Body = "Hello $uname,<br>Your new OTP is <b>$otp</b>. It expires in 2 minutes.";

        $mail->send();

        header("Location: verify_otp.php?msg=resent");
        exit();

    } catch (Exception $e) {
        echo "OTP could not be resent. Mailer Error: {$mail->ErrorInfo}";
        exit();
    }

} else {
    echo "User not found.";
    exit();
}
//this is resend otp file
?>
