<?php
include "config.php";
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Fetch user password from DB
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Check new password match
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update DB
                    $update = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt2 = $conn->prepare($update);
                    $stmt2->bind_param("si", $hashed_password, $user_id);

                    if ($stmt2->execute()) {
                        $message = "<p style='color:green;'>Password updated successfully!</p>";
                    } else {
                        $message = "<p style='color:red;'>Error updating password. Please try again.</p>";
                    }
                } else {
                    $message = "<p style='color:red;'>New password must be at least 6 characters long.</p>";
                }
            } else {
                $message = "<p style='color:red;'>New password and confirm password do not match.</p>";
            }
        } else {
            $message = "<p style='color:red;'>Current password is incorrect.</p>";
        }
    } else {
        $message = "<p style='color:red;'>User not found.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 25px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .back-link {
            display: block;
            margin-top: 15px;
            text-align: center;
        }
        .back-link a {
            text-decoration: none;
            color: #007bff;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔑 Change Password</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" required>

            <label for="new_password">New Password</label>
            <input type="password" name="new_password" required>

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit">Update Password</button>
        </form>
        <div class="back-link">
            <a href="settings.php">← Back to Settings</a>
        </div>
    </div>
</body>
</html>
