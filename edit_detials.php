<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt2 = $conn->prepare($update);
                    $stmt2->bind_param("si", $hashed_password, $user_id);

                    if ($stmt2->execute()) {
                        $message = "<div class='alert success'>Password updated successfully!</div>";
                    } else {
                        $message = "<div class='alert error'>Error updating password. Please try again.</div>";
                    }
                } else {
                    $message = "<div class='alert error'>Password must be at least 6 characters long.</div>";
                }
            } else {
                $message = "<div class='alert error'>New passwords do not match.</div>";
            }
        } else {
            $message = "<div class='alert error'>Current password is incorrect.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 380px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #111;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            transition: 0.3s;
        }
        input:focus {
            border-color: #000;
        }
        .btn {
            width: 100%;
            background: #000;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn:hover {
            background: #333;
        }
        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
        }
        .links {
            text-align: center;
            margin-top: 10px;
        }
        .links a {
            color: #000;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .back-btn {
            display: block;
            margin: 0 auto;
            width: 50%;
            text-align: center;
            margin-top: 20px;
            background: #000;
            color: #fff;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #333;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Change Password</h2>
    <?= $message ?>
    <form method="POST">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" name="change_password" class="btn">Update Password</button>
    </form>

    <div class="links">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>

    <a href="settings.php" class="back-btn">← Back to Settings</a>
</div>

</body>
</html>
