<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NoCap Press - Home</title>
  <link rel="stylesheet" href="stylehome.css"/>
  <style>
    .spa-section { display: none; }
    .spa-section.active { display: block; }
    .bottom-nav a.active { color: red; font-weight: bold; }

    .post-card {
      background: #fff;
      border: 1px solid #ccc;
      margin-bottom: 15px;
      padding: 15px;
      border-radius: 6px;
    }
    .post-title { font-size: 20px; margin: 0 0 10px; }
    .post-meta { font-size: 14px; color: gray; margin-bottom: 10px; }
    .post-excerpt { font-size: 16px; }
    .actions a {
      margin-right: 10px;
      text-decoration: none;
      color: #007BFF;
    }
    header.top-header {
      background: #111; color: #fff;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
    }
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background: #eee;
      display: flex;
      justify-content: space-around;
      padding: 10px 0;
    }
    .bottom-nav a {
      text-decoration: none;
      color: #333;
    }
  </style>
</head>
<body>

<!-- Header -->
<header class="top-header">
  <div class="username"><?php echo htmlspecialchars($email); ?></div>
  <div class="logo">NoCap Press</div>
</header>

<!-- HOME Section -->
<section id="home-section" class="spa-section active">
  <main class="content">
    <h2>All Blog Posts</h2><hr>
    <?php
    $query = "SELECT p.*, c.email FROM posts p JOIN credentials c ON p.user_id = c.user_id ORDER BY p.created_at DESC";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='post-card'>";
            echo "<div class='post-meta'><strong>" . htmlspecialchars($row['email']) . "</strong> | " . htmlspecialchars($row['created_at']) . "</div>";
            echo "<h3 class='post-title'>" . htmlspecialchars($row['title']) . "</h3>";
            echo "<p class='post-excerpt'>" . nl2br(substr(htmlspecialchars($row['content']), 0, 100)) . "...</p>";
            echo "<div class='actions'>";
            echo "<a href='view.php?pid=" . $row['post_id'] . "'>View</a>";
            if ($row['email'] === $email) {
                echo "<a href='edit.php?pid=" . $row['post_id'] . "'>Edit</a>";
                echo "<a href='delete.php?pid=" . $row['post_id'] . "' onclick=\"return confirm('Delete this post?');\">Delete</a>";
            }
            echo "</div></div>";
        }
    } else {
        echo "<p>No blog posts found.</p>";
    }
    ?>
  </main>
</section>

<!-- SEARCH Section -->
<section id="search-section" class="spa-section">
<h2>Search Blog</h2>
<form method="get" action="search.php">
    <input type="text" name="q" placeholder="Enter keyword">
    <input type="submit" value="Search">
</form>
</section>

<!-- CREATE Section -->
<section id="create-section" class="spa-section">
  <!-- <h2>Create Blog</h2>
  <form method="POST" action="create_blog.php">
    <input type="text" name="title" placeholder="Title" required /><br><br>
    <textarea name="content" placeholder="Write your blog here..." rows="5" required></textarea><br><br>
    <button type="submit">Publish</button>
  </form> -->
  <h2>Create Blog Post</h2>
<form method="post" action="create_blog.php">
    Title:<br><input type="text" name="title" required><br><br>
    Content:<br><textarea name="content" rows="6" cols="50" required></textarea><br><br>
    <input type="submit" name="submit" value="Post">
</form>
</section>

<!-- BOOKMARK Section -->
<section id="bookmark-section" class="spa-section">
  <h2>Bookmarks</h2>
  <p>[Display user bookmarks here]</p>
</section>

<!-- PROFILE Section -->
<section id="profile-section" class="spa-section">
  <h2>Your Profile</h2>
  <p>Email: <?php echo htmlspecialchars($email); ?></p>
  <a href="logout.php">Logout</a>
</section>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
  <a href="#" class="nav-icon active" data-target="home-section">Home</a>
  <a href="#" class="nav-icon" data-target="search-section">Search</a>
  <a href="#" class="nav-icon" data-target="create-section">Create</a>
  <a href="#" class="nav-icon" data-target="bookmark-section">Bookmark</a>
  <a href="#" class="nav-icon" data-target="profile-section">Profile</a>
</nav>

<script>
  const navLinks = document.querySelectorAll(".bottom-nav .nav-icon");
  const sections = document.querySelectorAll(".spa-section");

  navLinks.forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const targetId = link.getAttribute("data-target");

      sections.forEach(sec => sec.classList.remove("active"));
      document.getElementById(targetId).classList.add("active");

      navLinks.forEach(l => l.classList.remove("active"));
      link.classList.add("active");
    });
  });
</script>

</body>
</html>