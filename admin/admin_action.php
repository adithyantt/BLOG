<?php
include "../config.php";
session_start();

// ✅ Only allow admin/superadmin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    echo json_encode(["success" => false, "msg" => "Unauthorized"]);
    exit();
}
//admin action

// ✅ Parse incoming request
$data = json_decode(file_get_contents("php://input"), true);
$report_id = (int)($data['report_id'] ?? 0);
$action = $data['action'] ?? '';
$note = mysqli_real_escape_string($conn, $data['admin_note'] ?? "");

// ✅ Get report details
$reportRes = mysqli_query($conn, "SELECT * FROM reports WHERE report_id = $report_id");
$report = mysqli_fetch_assoc($reportRes);
if (!$report) {
    echo json_encode(["success" => false, "msg" => "Report not found"]);
    exit();
}

$user_id    = $report['reported_user_id'];
$post_id    = $report['reported_post_id'];
$comment_id = $report['reported_comment_id'];

$msg = "";

// ---------------- ACTION HANDLING ---------------- //

if ($action === "warn") {
    // Mark report reviewed
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='warn', admin_note='$note' WHERE report_id=$report_id");

    // Count total warnings on this post
    $warnCountRes = mysqli_query($conn, "SELECT COUNT(*) as c FROM reports WHERE reported_post_id=$post_id AND admin_action='warn'");
    $warnCount = mysqli_fetch_assoc($warnCountRes)['c'];

    if ($warnCount >= 3 && $post_id) {
        // Auto suspend post
        mysqli_query($conn, "UPDATE posts SET status='suspended' WHERE post_id=$post_id");
        $msg = "⚠️ Post automatically suspended after 3 warnings.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
            VALUES ($user_id, '🚫 Your post has been suspended after 3 warnings.', 'view.php?pid=$post_id', 'admin', NOW())");
    } else {
        $msg = "⚠️ Warning issued.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
            VALUES ($user_id, '⚠️ You have received a warning for your post.', 'view.php?pid=$post_id', 'admin', NOW())");
    }
}

elseif ($action === "suspend_post" && $post_id) {
    mysqli_query($conn, "UPDATE posts SET status='suspended' WHERE post_id=$post_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='suspend_post', admin_note='$note' WHERE report_id=$report_id");
    $msg = "🚫 Post suspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '🚫 Your post has been suspended by admin.', 'view.php?pid=$post_id', 'admin', NOW())");
}

elseif ($action === "unsuspend_post" && $post_id) {
    mysqli_query($conn, "UPDATE posts SET status='active' WHERE post_id=$post_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='unsuspend_post', admin_note='$note' WHERE report_id=$report_id");
    $msg = "✅ Post unsuspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '✅ Your post suspension has been lifted by admin.', 'view.php?pid=$post_id', 'admin', NOW())");
}

elseif ($action === "suspend_comment" && $comment_id) {
    mysqli_query($conn, "UPDATE comments SET status='suspended' WHERE comment_id=$comment_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='suspend_comment', admin_note='$note' WHERE report_id=$report_id");
    $msg = "💬 Comment suspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '🚫 Your comment has been suspended by admin.', NULL, 'admin', NOW())");
}

elseif ($action === "unsuspend_comment" && $comment_id) {
    mysqli_query($conn, "UPDATE comments SET status='active' WHERE comment_id=$comment_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='unsuspend_comment', admin_note='$note' WHERE report_id=$report_id");
    $msg = "💬 Comment unsuspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '✅ Your comment suspension has been lifted by admin.', NULL, 'admin', NOW())");
}

elseif ($action === "suspend_account" && $user_id) {
    mysqli_query($conn, "UPDATE credentials SET status='suspended' WHERE user_id=$user_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='suspend_account', admin_note='$note' WHERE report_id=$report_id");
    $msg = "👤 Account suspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '🚫 Your account has been suspended by admin.', NULL, 'admin', NOW())");
}

elseif ($action === "unsuspend_account" && $user_id) {
    mysqli_query($conn, "UPDATE credentials SET status='active' WHERE user_id=$user_id");
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='unsuspend_account', admin_note='$note' WHERE report_id=$report_id");
    $msg = "👤 Account unsuspended.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, created_at) 
        VALUES ($user_id, '✅ Your account suspension has been lifted by admin.', NULL, 'admin', NOW())");
}

elseif ($action === "dismiss") {
    mysqli_query($conn, "UPDATE reports SET status='reviewed', admin_action='dismiss', admin_note='$note' WHERE report_id=$report_id");
    $msg = "✅ Report dismissed.";
}

// ---------------- RESPONSE ---------------- //
echo json_encode(["success" => true, "msg" => $msg]);
