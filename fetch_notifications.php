<?php
include "config.php";
session_start();
header('Content-Type: application/json');

// Ensure logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit;
}

$email = $_SESSION['email'];
$res = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($res);
$user_id = $user['user_id'];

// Fetch latest notifications
$notifRes = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");

$notifications = [];
while ($row = mysqli_fetch_assoc($notifRes)) {
    $notifications[] = [
        'id' => $row['notification_id'],
        'type' => $row['type'],
        'message' => $row['message'],
        'created_at' => $row['created_at'],
        'is_read' => (int)$row['is_read']
    ];
}

echo json_encode(["success" => true, "notifications" => $notifications]);
?>
