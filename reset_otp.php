<?php
session_start();
include "config.php";

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$reset_email = $_SESSION['reset_email'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp']);

    // Fetch OTP from DB
    $stmt = mysqli_prepare($conn, "SELECT otp_code, otp_expires FROM credentials WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $reset_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $db_otp   = $row['otp_code'];
        $expires  = $row['otp_expires'];

        if ($db_otp === $input_otp) {
            if (strtotime($expires) >= time()) {
                // OTP valid → allow password reset
                $_SESSION['allow_password_reset'] = true;
                header("Location: new_password.php");
                exit();
            } else {
                $error = "OTP expired. Please request again.";
                unset($_SESSION['reset_email'], $_SESSION['reset_otp_expires']);
            }
        } else {
            $error = "Invalid OTP. Try again.";
        }
    } else {
        $error = "No user found. Please try again.";
        unset($_SESSION['reset_email'], $_SESSION['reset_otp_expires']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Verify OTP</title>
<style>
body { font-family: Arial, sans-serif; background: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
.container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
h2 { text-align: center; margin-bottom: 20px; }
input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #218838; }
.error { color: red; text-align: center; margin-bottom: 10px; }
.timer { text-align: center; margin-top: 10px; font-size: 14px; color: #333; }
</style>
<script>
// Countdown for OTP expiry (2 minutes)
let timeLeft = 120;
function startTimer() {
    let timer = setInterval(function() {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        document.getElementById("timer").innerText =
            "OTP expires in " + minutes + "m " + seconds + "s";
        timeLeft--;

        if (timeLeft < 0) {
            clearInterval(timer);
            document.getElementById("timer").innerText = "OTP expired. Please request again.";
        }
    }, 1000);
}
window.onload = startTimer;
</script>
</head>
<body>
<div class="container">
    <h2>Verify OTP</h2>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit">Verify OTP</button>
    </form>
    <div class="timer" id="timer"></div>
</div>
</body>
</html>
//this reset password 