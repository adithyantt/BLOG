<?php
include "../config.php";
session_start();

if (!isset($_SESSION['role']) ||
   !in_array(strtolower($_SESSION['role']), ['admin','superadmin'])) {
    header("Location: ../login.php");
    exit();
}

$pid    = intval($_GET['pid'] ?? 0);
$action = $_GET['action'] ?? '';
$rid    = intval($_GET['rid'] ?? 0);
$note   = mysqli_real_escape_string($conn, $_GET['note'] ?? '');

if (!$pid || !$action || !$rid) {
    header("Location: reports.php");
    exit();
}

// Get post owner
$post = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT user_id FROM posts WHERE post_id=$pid LIMIT 1"
));
if (!$post) { header("Location: reports.php"); exit(); }

$uid = $post['user_id'];
$admin_action = "";
$notify_msg = "";

// Generate link
$link = "view.php?pid=$pid";   // THIS FIXES THE REDIRECT PROBLEM


/* ======================= WARN ======================= */
if ($action === 'warn') {

    $admin_action = "Warned Post";
    $notify_msg = "⚠️ Your post has been flagged by the admin.";

    mysqli_query($conn, "
        INSERT INTO notifications (user_id, message, link, created_at)
        VALUES ($uid, '$notify_msg', '$link', NOW())
    ");
}


/* ======================= SUSPEND ======================= */
if ($action === 'suspend') {

    mysqli_query($conn, "UPDATE posts SET status='suspended' WHERE post_id=$pid");

    $admin_action = "Suspended Post";
    $notify_msg = "⛔ Your post has been suspended by the admin.";

    mysqli_query($conn, "
        INSERT INTO notifications (user_id, message, link, created_at)
        VALUES ($uid, '$notify_msg', '$link', NOW())
    ");
}


/* ======================= DISMISS ======================= */
if ($action === 'dismiss') {
    $admin_action = "Dismissed Report";
    // No notification
}


/* ======================= UPDATE REPORT ======================= */
mysqli_query($conn, "
    UPDATE reports 
    SET status='reviewed',
        admin_action='$admin_action',
        admin_note='$note'
    WHERE report_id=$rid
");


header("Location: reports.php");
exit();
?>
