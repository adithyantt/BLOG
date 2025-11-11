<?php
include "config.php";
session_start();

// ----------------- User login check -----------------
if (empty($_SESSION['email']) || empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$email   = $_SESSION['email'];

// ----------------- Fetch user info -----------------
$userRes = mysqli_query($conn, "SELECT user_id, uname, email, bio, profile_img, status, is_suspended FROM credentials WHERE user_id = $user_id LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);

if (!$currentUser) {
    header("Location: login.html");
    exit();
}

// ----------------- Suspended account check -----------------
$status = $currentUser['status'] ?? 'active';
$isSuspended = (int)($currentUser['is_suspended'] ?? 0);

if ($status === 'suspended' || $isSuspended === 1) {
    session_destroy();
    die("Your account has been suspended by admin.");
}

// ----------------- Current user info -----------------
$current_user_id = (int)$currentUser['user_id'];
$uname           = htmlspecialchars($currentUser['uname']);
$user_email      = htmlspecialchars($currentUser['email']);
$bio             = !empty($currentUser['bio']) ? htmlspecialchars($currentUser['bio']) : "No bio added yet.";

// ✅ Default profile image fallback check (prevents broken image)
if (!empty($currentUser['profile_img']) && file_exists($currentUser['profile_img'])) {
    $profile_img = htmlspecialchars($currentUser['profile_img']);
} else {
    $profile_img = "uploads/default_profile.png";
}

// ----------------- Notification count -----------------
$notif_count = 0;
$notifRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = $current_user_id AND is_read = 0");
if ($notifRes) {
    $notif_count = (int)mysqli_fetch_assoc($notifRes)['cnt'];
}

// ----------------- Stats: posts, drafts, bookmarks -----------------
$posts_count = 0;
$drafts_count = 0;
$bookmarks_count = 0;

$r1 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM posts WHERE user_id = $current_user_id AND (status IS NULL OR status='active')");
if ($r1) $posts_count = (int)mysqli_fetch_assoc($r1)['c'];

$r2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM posts WHERE user_id = $current_user_id AND status='draft'");
if ($r2) $drafts_count = (int)mysqli_fetch_assoc($r2)['c'];

$r3 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookmarks WHERE user_id = $current_user_id");
if ($r3) $bookmarks_count = (int)mysqli_fetch_assoc($r3)['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?php echo $uname; ?> — Profile | NoCapPress</title>
  <link rel="stylesheet" href="home.css">
  <style>
    /* Profile page specific */
    .profile-container {
      max-width: 860px;
      margin: 20px auto;
      background: #fff;
      padding: 28px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }
    .profile-top {
      display:flex;
      gap:20px;
      align-items:center;
      margin-bottom:18px;
    }
    .profile-img {
      width:110px;
      height:110px;
      border-radius:50%;
      object-fit:cover;
      border:2px solid #f0f0f0;
    }
    .profile-meta { flex:1; }
    .profile-name { font-size:22px; font-weight:700; color:#111; margin-bottom:4px; }
    .profile-email { color:#666; margin-bottom:8px; }
    .profile-bio { color:#333; font-size:15px; line-height:1.4; }
    .profile-actions { margin-top:14px; display:flex; gap:12px; flex-wrap:wrap; }
    .btn {
      padding:10px 14px;
      border-radius:8px;
      text-decoration:none;
      color:#fff;
      background:#0d6efd;
      display:inline-block;
      font-weight:600;
      transition: background 0.2s;
    }
    .btn:hover { background:#0b5ed7; }
    .btn.secondary { background:#6b7280; }
    .btn.danger { background:#dc3545; }
    .btn.danger:hover { background:#b02a37; }
    @media (max-width:720px){
      .profile-top { flex-direction:column; align-items:center; text-align:center; }
      .profile-meta{ width:100%; }
      .profile-actions { justify-content:center; }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="brand-title">NoCapPress</div>

<nav class="side-nav">
  <a href="home.php" class="menu-item">Home</a>
  <a href="search.php" class="menu-item">Search</a>
  <a href="create_blog.php" class="menu-item">Create</a>
  <a href="bookmarks.php" class="menu-item">Bookmark</a>
  <a href="profile.php" class="menu-item active-link">Profile</a>
</nav>

<!-- Top Header -->
<div class="top-header">
  <!-- Notifications -->
  <a href="notifications.php" style="text-decoration:none;color:inherit;">
    <div id="notif-bell" style="position:relative;display:inline-block;">
      🔔
      <span id="notif-count" style="position:absolute;top:-5px;right:-10px;background:red;color:white;border-radius:50%;padding:2px 6px;font-size:12px;">
        <?php echo (int)$notif_count; ?>
      </span>
    </div>
  </a>

  <!-- User Profile Icon -->
  <div class="profile-icon" style="display:flex;align-items:center;gap:8px;">
    <span style="font-size:15px;font-weight:600;"><?php echo $uname; ?></span>
    <a href="profile.php">
      <img src="<?php echo $profile_img; ?>" alt="Profile" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
    </a>
  </div>
</div>

<!-- Main content -->
<div class="main-content">
  <div class="profile-container">
    <div class="profile-top">
      <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
      <div class="profile-meta">
        <div class="profile-name"><?php echo $uname; ?></div>
        <div class="profile-email"><?php echo $user_email; ?></div>
        <div class="profile-bio"><?php echo $bio; ?></div>

        <div class="profile-actions">
          <a class="btn" href="edit_profile.php">Edit profile</a>
          <a class="btn" href="my_posts.php">My posts</a>
          <a class="btn" href="drafts.php">Saved drafts</a>
          <a class="btn secondary" href="settings.php">Settings</a>
          <!-- ✅ Logout button that fully destroys session -->
          <a class="btn danger" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:6px;">
      <div class="muted">Posts: <?php echo $posts_count; ?></div>
      <div class="muted">Drafts: <?php echo $drafts_count; ?></div>
      <div class="muted">Bookmarks: <?php echo $bookmarks_count; ?></div>
    </div>
  </div>
</div>

</body>
</html>
