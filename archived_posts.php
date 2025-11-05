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

// Fetch archived posts
$sql = "SELECT p.*, c.email, c.profile_img
        FROM archived_posts ap
        JOIN posts p ON ap.post_id = p.post_id
        JOIN credentials c ON p.user_id = c.user_id
        WHERE ap.user_id = $current_user_id
        ORDER BY ap.archived_at DESC";
$posts = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Archived Posts | NoCap Press</title>
  <link rel="stylesheet" href="home.css">
</head>
<body>

<!-- Sidebar -->
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
</div>

<!-- Main -->
<div class="main-content">
  <h2>Archived Posts</h2>
  <hr>
  <?php if ($posts && mysqli_num_rows($posts) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($posts)): ?>
      <div class="post-card">
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <?php if (!empty($row['img_url'])): ?>
          <div class="post-thumbnail">
            <img src="<?php echo htmlspecialchars($row['img_url']); ?>" alt="Thumbnail">
          </div>
        <?php endif; ?>
        <p><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 200))); ?>...</p>
        <a href="view.php?pid=<?php echo $row['post_id']; ?>">Read More</a>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>You haven't archived any posts yet.</p>
  <?php endif; ?>
</div>

</body>
</html>
