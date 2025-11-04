<?php
include "../config.php";
session_start();

// Only admins
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit();
}

$table = "";
$pk    = "";
$msg   = "";

switch ($type) {
    case 'user':
        $table = "credentials";
        $pk = "user_id";
        break;
    case 'post':
        $table = "posts";
        $pk = "post_id";
        break;
    case 'comment':
        $table = "comments";
        $pk = "comment_id";
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid type"]);
        exit();
}

// Fetch current status
$stmt = mysqli_prepare($conn, "SELECT status, user_id FROM $table WHERE $pk = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    echo json_encode(["success" => false, "message" => ucfirst($type) . " not found"]);
    exit();
}

$new_status = ($row['status'] === 'active') ? 'suspended' : 'active';

// Update status
$upd = mysqli_prepare($conn, "UPDATE $table SET status = ? WHERE $pk = ?");
mysqli_stmt_bind_param($upd, "si", $new_status, $id);
$ok = mysqli_stmt_execute($upd);

// Send notification if post or comment is suspended
if ($ok && $type === 'post') {
    $uid = $row['user_id'];
    $msg = ($new_status === 'suspended') 
        ? "Your post has been suspended by the admin." 
        : "Your post has been re-activated by the admin.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES ($uid, '$msg', NOW())");
}

if ($ok && $type === 'comment') {
    $uid = $row['user_id'];
    $msg = ($new_status === 'suspended') 
        ? "Your comment has been suspended by the admin." 
        : "Your comment has been re-activated by the admin.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES ($uid, '$msg', NOW())");
}

if ($ok) {
    echo json_encode(["success" => true, "new_status" => $new_status]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
