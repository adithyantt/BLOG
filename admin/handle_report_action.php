<?php
include "../config.php";
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$pid = intval($_GET['pid'] ?? 0);
$action = $_GET['action'] ?? '';

if ($pid && $action) {
    // Get post + user info
    $postData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id, warn_count FROM posts WHERE post_id=$pid"));
    if (!$postData) {
        header("Location: reports.php");
        exit();
    }
    $uid = $postData['user_id'];

    if ($action === "warn") {
        // Increase warning count
        mysqli_query($conn, "UPDATE posts SET warn_count = warn_count + 1 WHERE post_id=$pid");

        // Fetch updated warn count
        $warnData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT warn_count FROM posts WHERE post_id=$pid"));

        // If 3 warnings → auto-suspend
        if ($warnData['warn_count'] >= 3) {
            mysqli_query($conn, "UPDATE posts SET status='suspended' WHERE post_id=$pid");
            $msg = "Your post has been suspended by the admin due to repeated violations.";
        } else {
            $msg = "Your post or language is inappropriate and violates our guidelines.";
        }

        mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES ($uid, '$msg', NOW())");

    } elseif ($action === "suspend") {
        mysqli_query($conn, "UPDATE posts SET status='suspended' WHERE post_id=$pid");
        $msg = "Your post has been suspended by the admin due to repeated violations.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES ($uid, '$msg', NOW())");

    } elseif ($action === "delete") {
        mysqli_query($conn, "DELETE FROM posts WHERE post_id=$pid");
        $msg = "Your post has been permanently removed by the admin.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) VALUES ($uid, '$msg', NOW())");
    }
}

header("Location: reports.php");
exit();
?>
