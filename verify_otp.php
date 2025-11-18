<?php
session_start();
include "config.php";

// If user reached here without signup, redirect
if (!isset($_SESSION['pending_email'])) {
    header("Location: signup.html");
    exit();
}

$pending_email = $_SESSION['pending_email'];
$pending_role  = $_SESSION['pending_role'];
$error = '';

if (isset($_POST['verify'])) {
    $input_otp = trim($_POST['otp']);

    // Fetch OTP from DB
    $stmt = mysqli_prepare($conn, "SELECT otp_code, otp_expires, user_id, uname, role FROM credentials WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $pending_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $db_otp   = $row['otp_code'];
        $expires  = $row['otp_expires'];

        if ($db_otp === $input_otp) {

            // Check OTP expiry (2 minutes)
            if (strtotime($expires) >= time()) {

                // OTP valid → Login user
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['email']   = $pending_email;
                $_SESSION['uname']   = $row['uname'];
                $_SESSION['role']    = $row['role'];

                // Mark user as active
                $status = "active";
                $update_stmt = mysqli_prepare($conn, "UPDATE credentials SET status=? WHERE user_id=?");
                mysqli_stmt_bind_param($update_stmt, "si", $status, $row['user_id']);
                mysqli_stmt_execute($update_stmt);

                // Clear pending OTP session (BUT keep signup_data)
                unset($_SESSION['pending_email'], $_SESSION['pending_role']);

                // Redirect based on role
                if ($row['role'] === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    if (isset($_SESSION['new_user_signup']) && $_SESSION['new_user_signup'] === true) {
                        unset($_SESSION['new_user_signup']);
                        header("Location: profile_setup.php");
                    } else {
                        header("Location: home.php");
                    }
                }
                exit();
            } else {
                $error = "OTP expired (2 minutes). Please request again.";
                unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['new_user_signup']);
            }
        } else {
            $error = "Invalid OTP. Try again.";
        }
    } else {
        $error = "User not found. Please register again.";
        unset($_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['new_user_signup']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - NoCapPress</title>
<style>
body { font-family: Arial, sans-serif; background: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
.container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
h2 { text-align: center; margin-bottom: 10px; }
input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
button { width: 100%; padding: 10px; background: #111212ff; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #141415ff; }
.error { color: red; text-align: center; margin-bottom: 10px; }
.resend { text-align: center; margin-top: 10px; font-size: 14px; }
.resend a { color: #3c1df1ff; text-decoration: none; }
.resend a:hover { text-decoration: underline; }
.timer { text-align: center; margin-top: 10px; font-size: 14px; color: #333; }
.edit-email { text-align:center; margin-top:10px; font-size:14px; }
.edit-email a { color:#3c1df1ff; text-decoration:none; }
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

    <!-- Display error -->
    <?php if($error) echo "<div class='error'>$error</div>"; ?>

    <!-- Display full email + edit option -->
    <p style="text-align:center;margin-bottom:15px;">
        OTP sent to: <b><?php echo $pending_email; ?></b><br>
        <a href="edit_email.php" style="color:#3c1df1ff;text-decoration:none;">Wrong email? Edit email</a>
    </p>

    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit" name="verify">Verify</button>
    

    </form>

    <div class="timer" id="timer"></div>

    <div class="resend">
        Didn't receive OTP? <a href="resend_otp.php">Resend OTP</a>
    </div>
</div>
</body>
</html>
