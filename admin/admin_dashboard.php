<?php
include "../config.php";
session_start();

// Protect admin area
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: ../login.php");
    exit();
}

// Fetch stats
$users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM credentials"))['total'];
$posts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM posts"))['total'];
$comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM comments"))['total'];
$likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes"))['total'];

// Recent users
$recent_users = mysqli_query($conn, "SELECT uname, email, created_at FROM credentials ORDER BY created_at DESC LIMIT 5");

// Recent posts
$recent_posts = mysqli_query($conn, "SELECT title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | NoCapPress</title>
  <link rel="stylesheet" href="./admin_style.css">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>NoCapPress Admin</h2>
      <ul>
        <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
        <li><a href="manage_users.php">👤 Manage Users</a></li>
        <li><a href="manage_posts.php">📝 Manage Posts</a></li>
        <li><a href="reports.php">⚠️ Reports</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header>
        <h1>Hello, <?php echo $_SESSION['uname']; ?> 👋</h1>
      </header>

      <!-- Stats Cards -->
      <section class="stats">
        <div class="card">Users <span><?php echo $users; ?></span></div>
        <div class="card">Posts <span><?php echo $posts; ?></span></div>
        <div class="card">Comments <span><?php echo $comments; ?></span></div>
        <div class="card">Likes <span><?php echo $likes; ?></span></div>
      </section>

      <!-- Recent Activity -->
      <section class="recent">
        <div class="recent-box">
          <h3>Recent Users</h3>
          <table>
            <tr><th>Name</th><th>Email</th><th>Joined</th></tr>
            <?php while ($u = mysqli_fetch_assoc($recent_users)) { ?>
              <tr>
                <td><?php echo $u['uname']; ?></td>
                <td><?php echo $u['email']; ?></td>
                <td><?php echo $u['created_at']; ?></td>
              </tr>
            <?php } ?>
          </table>
        </div>

        <div class="recent-box">
          <h3>Recent Posts</h3>
          <table>
            <tr><th>Title</th><th>Date</th></tr>
            <?php while ($p = mysqli_fetch_assoc($recent_posts)) { ?>
              <tr>
                <td><?php echo $p['title']; ?></td>
                <td><?php echo $p['created_at']; ?></td>
              </tr>
            <?php } ?>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
