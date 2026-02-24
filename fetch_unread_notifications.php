<?php
include "config.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit;
}

$email = $_SESSION['email'];
$res = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($res);
$user_id = $user['user_id'];

// Count unread notification
$notifRes = mysqli_query($conn, "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id=$user_id AND is_read=0");
$notifCount = mysqli_fetch_assoc($notifRes)['unread_count'] ?? 0;

echo json_encode(["success" => true, "unread_count" => (int)$notifCount]);
