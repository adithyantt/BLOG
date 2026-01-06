<?php
session_start();

// Ensure signup data exists (otherwise redirect safely)
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.html");
    exit();
}

// Safely extract values with fallback setting 
$uname = isset($_SESSION['signup_data']['uname']) ? urlencode($_SESSION['signup_data']['uname']) : '';
$email = isset($_SESSION['signup_data']['email']) ? urlencode($_SESSION['signup_data']['email']) : '';
$phone = isset($_SESSION['signup_data']['phone']) ? urlencode($_SESSION['signup_data']['phone']) : '';
$role  = isset($_SESSION['signup_data']['role'])  ? urlencode($_SESSION['signup_data']['role'])  : '';

// Clear only OTP-related sessions //
unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['new_user_signup']);

// Redirect with URL parameters
header("Location: signup.html?name=$uname&email=$email&phone=$phone&role=$role");
exit();
?>
