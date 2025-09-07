<?php
include "../config.php";
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $msg = mysqli_real_escape_string($conn, trim($_POST['message']));

    // Send message to all users
    $users = mysqli_query($conn, "SELECT user_id FROM credentials");

    while ($user = mysqli_fetch_assoc($users)) {
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES ({$user['user_id']}, '$msg', 0, NOW())");
    }

    echo "Message sent to all users.";
}
?>

<form method="POST">
    <textarea name="message" placeholder="Write admin message here..." required style="width:100%; height:100px;"></textarea><br><br>
    <button type="submit">Send Notification</button>
</form>
