<?php
include "config.php";
session_start();

if (!isset($_GET['category'])) {
    die("Category not specified");
}

//this is the category setting
$category = mysqli_real_escape_string($conn, $_GET['category']);

$result = mysqli_query($conn, "
    SELECT p.*, c.uname, c.email 
    FROM posts p
    JOIN credentials c ON p.user_id = c.user_id
    WHERE p.category = '$category'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Posts in <?php echo htmlspecialchars($category); ?></title>
  <link rel="stylesheet" href="home.css">
</head>
<body>
  <h2>Posts in "<?php echo htmlspecialchars($category); ?>"</h2>
  <?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="post-card">
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars(substr($row['content'],0,150))); ?>...</p>
        <small>By <?php echo htmlspecialchars($row['uname']); ?> (<?php echo $row['email']; ?>)</small>
        <a href="view.php?pid=<?php echo $row['post_id']; ?>">Read More</a>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No posts found in this category.</p>
  <?php endif; ?>
</body>
</html>
