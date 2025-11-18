<?php
session_start();
include "config.php";

// Load PHPMailer
require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uname = trim($_POST['uname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role  = strtolower(trim($_POST['role']));
    $pwd   = $_POST['pwd'];
    $confirm_pwd = $_POST['confirm_password'];
    $admin_code  = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';

    // Store signup data for editing email later
    $_SESSION['signup_data'] = [
    "uname" => $uname,
    "email" => $email,
    "phone" => $phone,
    "role"  => $role
];

    // --- Basic validation ---
    if (empty($uname) || empty($email) || empty($phone) || empty($role) || empty($pwd) || empty($confirm_pwd)) {
        die("Please fill in all fields.");
    }
    if ($pwd !== $confirm_pwd) {
        die("Passwords do not match.");
    }
    // Strong password validation (letter + number + special char, min 6 chars)
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[!@#$%^&*]).{6,}$/', $pwd)) {
        die("Password must be at least 6 characters and include one letter, one number, and one special character.");
    }


    // --- Admin code check ---
    $correct_admin_code = "SECRET123";
    if ($role === 'admin' && $admin_code !== $correct_admin_code) {
        die("Invalid admin code.");
    }

    // --- Check if email already exists ---
    $stmt = mysqli_prepare($conn, "SELECT * FROM credentials WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email already registered.'); window.location.href='signup.html';</script>";
        exit();
    }

    // --- Hash password ---
    $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);

    // --- Insert new user with status='pending' ---
    $status = 'pending';
    $stmt = mysqli_prepare($conn, "INSERT INTO credentials (uname,email,phone,role,pwd,status) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $uname, $email, $phone, $role, $hashed_pwd, $status);
    if (!mysqli_stmt_execute($stmt)) {
        die("Error inserting user: " . mysqli_error($conn));
    }

    // --- Generate OTP (6 digits) ---
    $otp = rand(100000, 999999);
    $expires = date("Y-m-d H:i:s", strtotime("+2 minutes"));

    // --- Save OTP in DB ---
    $stmt = mysqli_prepare($conn, "UPDATE credentials SET otp_code=?, otp_expires=? WHERE email=?");
    mysqli_stmt_bind_param($stmt, "sss", $otp, $expires, $email);
    mysqli_stmt_execute($stmt);

    // --- Send OTP email ---
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'vivekkrishna960@gmail.com'; // sender
        $mail->Password   = 'vspsxiaiqtoefxgb';          // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Disable SSL verification for localhost
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
        $mail->Subject = 'Your OTP for NoCapPress';
        $mail->Body    = "Hello $uname,<br>Your OTP is <b>$otp</b>. It expires in <b>2 minutes</b>.";

        $mail->send();

        // --- Save session for OTP verification ---
        $_SESSION['pending_email'] = $email;
        $_SESSION['pending_role']  = $role;
        $_SESSION['otp_expires']   = strtotime($expires);
        $_SESSION['new_user_signup'] = true; // <-- flag new user

        // --- Redirect to verify OTP ---
        header("Location: verify_otp.php");
        exit();

    } catch (Exception $e) {
        echo "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
        exit();
    }
}
?>
