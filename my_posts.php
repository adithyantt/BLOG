<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];

// Fetch current user
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;

if (!$current_user_id) {
    echo "User not found.";
    exit();
}

// Fetch user's posts
$sql = "SELECT p.*, c.uname, c.profile_img
        FROM posts p
        JOIN credentials c ON p.user_id = c.user_id
        WHERE p.user_id = $current_user_id
        ORDER BY p.created_at DESC";
$posts = mysqli_query($conn, $sql);

// Fetch notification count
$notif_count = 0;
$notifRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = $current_user_id AND is_read = 0");
if ($notifRes) $notif_count = (int)mysqli_fetch_assoc($notifRes)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Posts | NoCap Press</title>
<link rel="stylesheet" href="home.css">
<style>
/* Main Content */
.main-content { margin-left: 220px; padding: 80px 20px 20px; }
h2 { font-size: 24px; font-weight: 700; margin-bottom: 20px; }

/* Post Card */
.post-card { display:flex; justify-content:space-between; gap:15px; padding:20px; border-radius:12px; background:#fff; margin-bottom:15px; box-shadow:0 4px 16px rgba(0,0,0,0.05); transition:transform 0.2s;}
.post-card:hover { transform: translateY(-2px); }

.post-content { flex:1; }
.post-meta { display:flex; align-items:center; gap:8px; font-size:13px; color:#555; margin-bottom:6px; }
.post-meta img { width:28px; height:28px; border-radius:50%; object-fit:cover; }
.post-status { font-size:12px; font-weight:600; color:white; padding:2px 6px; border-radius:4px; margin-left:6px; }
.status-published { background:#10b981; }
.status-draft { background:#f59e0b; }
.post-title { font-size:18px; font-weight:700; margin:0 0 6px; color:#111; }
.post-excerpt { font-size:14px; color:#333; line-height:1.5; max-height:4.5em; overflow:hidden; margin:0 0 8px; }

.post-actions { display:flex; gap:8px; margin-top:8px; }
.post-actions a { text-decoration:none; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; transition:.2s; }
.read-link { background:#0d6efd; color:#fff; }
.read-link:hover { background:#0b5ed7; }
.edit-link { background:#6b7280; color:#fff; }
.edit-link:hover { background:#4b5563; }
.delete-link { background:#dc3545; color:#fff; }
.delete-link:hover { background:#b91c1c; }

.post-thumbnail img { width:120px; height:80px; object-fit:cover; border-radius:8px; }

/* Muted message */
.muted { color:#555; font-size:14px; }

/* Responsive */
@media(max-width:768px) {
  .post-card { flex-direction: column; }
  .post-thumbnail img { width:100%; height:auto; }
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
    <div id="notif-bell" style="position:relative;display:inline-block;">🔔
      <span id="notif-count" style="position:absolute;top:-5px;right:-10px;background:red;color:white;border-radius:50%;padding:2px 6px;font-size:12px;">
        <?php echo (int)$notif_count; ?>
      </span>
    </div>
  </a>
  <!-- User -->
  <div class="profile-icon" style="display:flex;align-items:center;gap:8px;">
    <span style="font-size:15px;font-weight:600;"><?php echo htmlspecialchars($currentUser['uname']); ?></span>
    <a href="profile.php">
      <img src="<?php echo htmlspecialchars($currentUser['profile_img']); ?>" alt="Profile" style="width:32px;height:32px;border-radius:50%;">
    </a>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <h2>My Posts</h2>

  <?php if ($posts && mysqli_num_rows($posts) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($posts)): ?>
      <div class="post-card">
        <div class="post-content">
          <div class="post-meta">
            <img src="<?php echo htmlspecialchars($row['profile_img']); ?>" alt="Author">
            <span><?php echo htmlspecialchars($row['uname']); ?></span>
            <span class="post-status <?php echo $row['status']=='draft'?'status-draft':'status-published'; ?>">
              <?php echo ucfirst($row['status']); ?>
            </span>
          </div>
          <h3 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h3>
          <p class="post-excerpt"><?php echo nl2br(htmlspecialchars(substr($row['content'],0,200))); ?>...</p>
          <div class="post-actions">
            <a href="view.php?pid=<?php echo $row['post_id']; ?>" class="read-link">View</a>
            <?php if($row['status']=='draft'): ?>
              <a href="create_blog.php?post_id=<?php echo $row['post_id']; ?>" class="edit-link">Edit</a>
            <?php else: ?>
              <a href="edit.php?pid=<?php echo $row['post_id']; ?>" class="edit-link">Edit</a>
            <?php endif; ?>
            <a href="delete_post.php?pid=<?php echo $row['post_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
          </div>
        </div>
        <?php if (!empty($row['img_url'])): ?>
          <div class="post-thumbnail">
            <img src="<?php echo htmlspecialchars($row['img_url']); ?>" alt="Thumbnail">
          </div>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="muted">You haven't created any posts yet.</p>
  <?php endif; ?>
</div>

</body>
</html>
//this is my post