<?php
session_start();
include "config.php";

// Only allow if OTP was verified
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['allow_password_reset']) || $_SESSION['allow_password_reset'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$reset_email = $_SESSION['reset_email'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd = $_POST['pwd'];
    $confirm_pwd = $_POST['confirm_pwd'];

    if (empty($pwd) || empty($confirm_pwd)) {
        $error = "Please fill in all fields.";
    } elseif ($pwd !== $confirm_pwd) {
        $error = "Passwords do not match.";
    } elseif (strlen($pwd) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Hash new password
        $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "UPDATE credentials SET pwd=?, otp_code=NULL, otp_expires=NULL WHERE email=?");
        mysqli_stmt_bind_param($stmt, "ss", $hashed_pwd, $reset_email);

        if (mysqli_stmt_execute($stmt)) {
            // Clear reset session
            unset($_SESSION['reset_email'], $_SESSION['allow_password_reset']);

            $success = "Password successfully reset! You can now <a href='login.html'>login</a>.";
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password</title>
<style>
body { font-family: Arial, sans-serif; background: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
.container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
h2 { text-align: center; margin-bottom: 20px; }
input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #007BFF; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #0056b3; }
.error { color: red; text-align: center; margin-bottom: 10px; }
.success { color: green; text-align: center; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="container">
    <h2>Set New Password</h2>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <?php if($success) echo "<div class='success'>$success</div>"; ?>
    <?php if(!$success): ?>
    <form method="POST">
        <input type="password" name="pwd" placeholder="New Password" required>
        <input type="password" name="confirm_pwd" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
