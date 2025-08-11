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

    /* Post styling */
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

    /* Header */
    header.top-header {
      background: #111; color: #fff;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
    }

    /* Bottom navigation */
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

    /* Top tab navigation inside home */
    .home-tabs {
      display: flex;
      background: #f2f2f2;
      padding: 8px;
    }
    .home-tabs button {
      flex: 1;
      padding: 10px;
      border: none;
      background: none;
      cursor: pointer;
      font-size: 16px;
    }
    .home-tabs button.active {
      font-weight: bold;
      border-bottom: 2px solid black;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
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
  <!-- Top Tabs -->
  <div class="home-tabs">
    <button class="tab-btn active" data-tab="for-you-tab">For You</button>
    <button class="tab-btn" data-tab="following-tab">Following</button>
    <button class="tab-btn" data-tab="category-tab">Category</button>
  </div>

  <!-- Tab Content -->
  <div id="for-you-tab" class="tab-content active">
    <h2>For You</h2><hr>
    <?php
    $query = "SELECT p.*, c.email 
              FROM posts p 
              JOIN credentials c ON p.user_id = c.user_id 
              ORDER BY p.created_at DESC";
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
  </div>

  <div id="following-tab" class="tab-content">
    <h2>Following</h2>
    <?php
// Get current logged-in user's ID
$current_user_email = $_SESSION['email'];
$current_user_query = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email = '" . mysqli_real_escape_string($conn, $current_user_email) . "'");
$current_user_data = mysqli_fetch_assoc($current_user_query);
$current_user_id = $current_user_data['user_id'];

// Fetch posts from people the user follows
$sql_following = "
    SELECT p.*, c.email 
    FROM posts p
    JOIN credentials c ON p.user_id = c.user_id
    WHERE p.user_id IN (
        SELECT following_id 
        FROM follows 
        WHERE follower_id = $current_user_id
    )
    ORDER BY p.created_at DESC
";

$result_following = mysqli_query($conn, $sql_following);

if (mysqli_num_rows($result_following) > 0) {
    while ($post = mysqli_fetch_assoc($result_following)) {
        echo "<div class='post-card'>";
        echo "<div class='post-meta'><strong>" . htmlspecialchars($post['email']) . "</strong> | " . htmlspecialchars($post['created_at']) . "</div>";
        echo "<h3 class='post-title'>" . htmlspecialchars($post['title']) . "</h3>";
        echo "<p class='post-excerpt'>" . nl2br(substr(htmlspecialchars($post['content']), 0, 100)) . "...</p>";
        echo "<div class='actions'><a href='view.php?pid=" . $post['post_id'] . "'>View</a></div>";
        echo "</div>";
    }
} else {
    echo "<p>No posts from people you follow yet.</p>";
}
?>

  </div>

  <div id="category-tab" class="tab-content">
    <h2>Category</h2>
    <p>[Show posts by category here]</p>
  </div>
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
  <hr>
  <?php
  $userQuery = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "'");
  if ($userQuery && mysqli_num_rows($userQuery) > 0) {
      $user = mysqli_fetch_assoc($userQuery);
      $user_id = (int)$user['user_id'];

      $bookmarkQuery = "
        SELECT p.*, c.email 
        FROM bookmarks b
        JOIN posts p ON b.post_id = p.post_id
        JOIN credentials c ON p.user_id = c.user_id
        WHERE b.user_id = $user_id
        ORDER BY b.bookmark_id DESC
      ";
      $bookmarkResult = mysqli_query($conn, $bookmarkQuery);

      if ($bookmarkResult && mysqli_num_rows($bookmarkResult) > 0) {
          while ($row = mysqli_fetch_assoc($bookmarkResult)) {
              echo "<div class='post-card'>";
              echo "<div class='post-meta'><strong>" . htmlspecialchars($row['email']) . "</strong> | " . htmlspecialchars($row['created_at']) . "</div>";
              echo "<h3 class='post-title'>" . htmlspecialchars($row['title']) . "</h3>";
              echo "<p class='post-excerpt'>" . nl2br(substr(htmlspecialchars($row['content']), 0, 100)) . "...</p>";
              echo "<div class='actions'><a href='view.php?pid=" . $row['post_id'] . "'>View</a></div>";
              echo "</div>";
          }
      } else {
          echo "<p>No bookmarks yet.</p>";
      }
  } else {
      echo "<p>Error: User not found.</p>";
  }
  ?>
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
  // Bottom Nav SPA
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

  // Top Tab Navigation for Home
  const tabButtons = document.querySelectorAll(".home-tabs .tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      tabButtons.forEach(b => b.classList.remove("active"));
      tabContents.forEach(c => c.classList.remove("active"));

      btn.classList.add("active");
      document.getElementById(btn.dataset.tab).classList.add("active");
    });
  });
</script>

</body>
</html>
