<?php
// admin_action.php
include "config.php";
session_start();
header('Content-Type: application/json');

// check admin
if (!isset($_SESSION['email'])) {
    echo json_encode(['success'=>false,'msg'=>'Not logged in']);
    exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$roleRes = mysqli_query($conn, "SELECT user_id, role FROM credentials WHERE email='$email' LIMIT 1");
$roleRow = mysqli_fetch_assoc($roleRes);
if (!$roleRow || $roleRow['role'] !== 'admin') {
    echo json_encode(['success'=>false,'msg'=>'Access denied']);
    exit();
}

// get request
$input = json_decode(file_get_contents('php://input'), true);
$report_id = isset($input['report_id']) ? (int)$input['report_id'] : 0;
$action    = isset($input['action']) ? $input['action'] : '';
$admin_note = isset($input['admin_note']) ? trim($input['admin_note']) : '';

if (!$report_id || !$action) {
    echo json_encode(['success'=>false,'msg'=>'Invalid request']);
    exit();
}

// fetch report
$rRes = mysqli_query($conn, "SELECT * FROM reports WHERE report_id = $report_id LIMIT 1");
if (!$rRes || mysqli_num_rows($rRes) === 0) {
    echo json_encode(['success'=>false,'msg'=>'Report not found']);
    exit();
}
$report = mysqli_fetch_assoc($rRes);
$reported_user = (int)$report['reported_user_id'];
$target_type = $report['target_type'];
$target_id = (int)$report['target_id'];

// helper: insert admin notification
function sendAdminNotification($conn, $to_user_id, $message, $link = null) {
    $to_user_id = (int)$to_user_id;
    $msgEsc = mysqli_real_escape_string($conn, $message);
    if ($link !== null) {
        $linkEsc = mysqli_real_escape_string($conn, $link);
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, type, is_read, created_at) VALUES ($to_user_id, '$msgEsc', '$linkEsc', 'admin', 0, NOW())");
    } else {
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES ($to_user_id, '$msgEsc', 'admin', 0, NOW())");
    }
}

switch ($action) {

  case 'dismiss':
    mysqli_query($conn, "UPDATE reports SET status='closed', admin_action='dismiss', admin_note='".mysqli_real_escape_string($conn,$admin_note)."', action_at=NOW() WHERE report_id=$report_id");
    echo json_encode(['success'=>true,'msg'=>'Report dismissed']);
    exit();

  case 'warn':
    $message = "Warning from admin: " . ($admin_note ?: 'Please follow site rules.');
    sendAdminNotification($conn, $reported_user, $message, null);
    mysqli_query($conn, "UPDATE reports SET status='closed', admin_action='warn', admin_note='".mysqli_real_escape_string($conn,$admin_note)."', action_at=NOW() WHERE report_id=$report_id");
    echo json_encode(['success'=>true,'msg'=>'User warned and notification sent']);
    exit();

  case 'suspend_post':
    if ($target_type !== 'post') {
      echo json_encode(['success'=>false,'msg'=>'Target is not a post']);
      exit();
    }
    // suspend post
    mysqli_query($conn, "UPDATE posts SET is_suspended=1 WHERE post_id=$target_id");
    // notify post owner (fetch owner)
    $postRes = mysqli_query($conn, "SELECT user_id, title FROM posts WHERE post_id=$target_id LIMIT 1");
    $postOwner = 0; $postTitle = '';
    if ($postRes && mysqli_num_rows($postRes) > 0) {
      $p = mysqli_fetch_assoc($postRes);
      $postOwner = (int)$p['user_id'];
      $postTitle = $p['title'] ?? '';
    }
    if ($postOwner) {
      $message = "Your post was suspended by admin." . ($admin_note ? " Reason: $admin_note" : "");
      $link = "view.php?pid=" . $target_id;
      sendAdminNotification($conn, $postOwner, $message, $link);
    }
    mysqli_query($conn, "UPDATE reports SET status='closed', admin_action='suspend_post', admin_note='".mysqli_real_escape_string($conn,$admin_note)."', action_at=NOW() WHERE report_id=$report_id");
    echo json_encode(['success'=>true,'msg'=>'Post suspended and owner notified']);
    exit();

  case 'suspend_comment':
    if ($target_type !== 'comment') {
      echo json_encode(['success'=>false,'msg'=>'Target is not a comment']);
      exit();
    }
    // suspend comment
    mysqli_query($conn, "UPDATE comments SET is_suspended=1 WHERE comment_id=$target_id");
    // notify the comment owner
    $cRes = mysqli_query($conn, "SELECT user_id, post_id FROM comments WHERE comment_id=$target_id LIMIT 1");
    $cOwner = 0; $cPost = 0;
    if ($cRes && mysqli_num_rows($cRes) > 0) {
      $cR = mysqli_fetch_assoc($cRes);
      $cOwner = (int)$cR['user_id'];
      $cPost = (int)$cR['post_id'];
    }
    if ($cOwner) {
      $message = "Your comment was suspended by admin." . ($admin_note ? " Reason: $admin_note" : "");
      $link = $cPost ? "view.php?pid=" . $cPost : null;
      sendAdminNotification($conn, $cOwner, $message, $link);
    }
    mysqli_query($conn, "UPDATE reports SET status='closed', admin_action='suspend_comment', admin_note='".mysqli_real_escape_string($conn,$admin_note)."', action_at=NOW() WHERE report_id=$report_id");
    echo json_encode(['success'=>true,'msg'=>'Comment suspended and owner notified']);
    exit();

  case 'suspend_account':
    // suspend account
    mysqli_query($conn, "UPDATE credentials SET is_suspended=1 WHERE user_id=$reported_user");
    $message = "Your account has been suspended by admin." . ($admin_note ? " Reason: $admin_note" : "");
    sendAdminNotification($conn, $reported_user, $message, null);
    mysqli_query($conn, "UPDATE reports SET status='closed', admin_action='suspend_account', admin_note='".mysqli_real_escape_string($conn,$admin_note)."', action_at=NOW() WHERE report_id=$report_id");
    echo json_encode(['success'=>true,'msg'=>'Account suspended and user notified']);
    exit();

  default:
    echo json_encode(['success'=>false,'msg'=>'Unknown action']);
    exit();
}
