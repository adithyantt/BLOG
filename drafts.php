<?php
session_start();
include "config.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];

// Fetch current users
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;
$currentUserName = $currentUser['uname'];
$currentUserImg = !empty($currentUser['profile_img']) ? $currentUser['profile_img'] : "uploads/default_profile.png";

// Fetch drafts
$sql = "SELECT * FROM posts WHERE user_id='$current_user_id' AND status='draft' ORDER BY updated_at DESC";
$result = mysqli_query($conn, $sql);

// Notification count
$notif_count = 0;
$notifRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = $current_user_id AND is_read = 0");
if ($notifRes) $notif_count = (int)mysqli_fetch_assoc($notifRes)['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Drafts | NoCap Press</title>
<link rel="stylesheet" href="home.css">
<style>
/* Sidebar & Header */
.side-nav { width: 220px; position: fixed; top:0; left:0; bottom:0; background:#f8f8f8; padding-top:60px; }
.side-nav .menu-item { display:block; padding:12px 20px; color:#070707; text-decoration:none; }
.side-nav .active-link { background:#ddd; }

.top-header { position:fixed; left:0; right:0; height:60px; display:flex; justify-content:space-between; align-items:center; padding:0 20px; border-bottom:1px solid #ddd; background:#fff; z-index:10; }
.top-header .site-title { font-weight:700; font-size:20px; }
.top-header .profile { display:flex; align-items:center; gap:10px; }
.top-header .profile img { width:36px; height:36px; border-radius:50%; }

/* Main content */
.main-content { margin-left:220px; margin-top:60px; padding:20px; }

/* Post card styling */
.post-card { display:flex; justify-content:space-between; gap:12px; padding:15px; border-radius:10px; background:#fff; margin-bottom:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.post-content { flex:1; }
.post-meta { font-size:13px; color:#555; margin-bottom:6px; }
.post-title { font-size:18px; font-weight:700; margin:0 0 6px; color:#111; }
.post-excerpt { font-size:14px; color:#333; line-height:1.5; max-height:4.5em; overflow:hidden; margin:0 0 8px; }
.post-actions { display:flex; gap:8px; margin-top:6px; }
.post-actions a { 
    text-decoration:none; 
    padding:6px 10px; 
    border-radius:6px; 
    font-size:13px; 
    font-weight:600; 
    transition:.2s; 
    background:#6b7280; 
    color:white; 
}

/* Delete button specifically */
.post-actions a.delete-link {
    background: #dc3545;
}

.post-actions a.delete-link:hover {
    background: #b91c1c;
}

/* Edit button retains default */
.post-actions a.edit-link {
    background: #6b7280;
}
.post-actions a.edit-link:hover {
    background: #4b5563;
}

.muted { color:#555; }
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
  <div class="site-title">NoCap Press</div>
  <div class="profile">
    <img src="<?= $currentUserImg ?>" alt="Profile">
    <span><?= htmlspecialchars($currentUserName) ?></span>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <h2>My Drafts</h2>

  <?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="post-card">
        <div class="post-content">
          <div class="post-meta">Last edited: <?= date("M d, Y H:i", strtotime($row['updated_at'])); ?></div>
          <div class="post-title"><?= htmlspecialchars($row['title']); ?></div>
          <div class="post-excerpt">
            <?= htmlspecialchars(substr($row['content'], 0, 150)) . (strlen($row['content']) > 150 ? "..." : ""); ?>
          </div>
          <div class="post-actions">
            <a href="create_blog.php?post_id=<?= $row['post_id']; ?>">✏️ Edit</a>
           <a href="delete_draft.php?post_id=<?= $row['post_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this draft?');">🗑️ Delete</a>

          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="muted">No drafts found. <a href="create_blog.php">Start a new post</a>.</p>
  <?php endif; ?>
</div>

</body>
</html>
