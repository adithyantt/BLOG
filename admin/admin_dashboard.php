<?php
include "../config.php";
session_start();

// Protect admin area
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: ../login.php");
    exit();
}
//admin dashboard 

// Fetch stats
$users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM credentials WHERE role !='admin'"))['total'];
$posts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM posts"))['total'];
$comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM comments"))['total'];
$likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes"))['total'];

// Recent users
$recent_users = mysqli_query($conn, "SELECT uname, email, created_at FROM credentials WHERE role!='admin' ORDER BY created_at DESC LIMIT 5");

// Recent posts
$recent_posts = mysqli_query($conn, "SELECT title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | NoCapPress</title>
  <link rel="stylesheet" href="./admin_style.css">
  <style>
    /* Match the manage_users.php table look */
    .dashboard-wrapper {
      display: flex;
      flex-direction: column;
      gap: 25px;
      margin-top: 20px;
    }

    .recent-box {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      overflow-x: auto;
      transition: transform 0.2s ease, box-shadow 0.3s ease;
    }

    .recent-box:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .recent-box h3 {
      font-size: 1.1rem;
      margin-bottom: 15px;
      color: #111827;
      border-left: 4px solid #2563eb;
      padding-left: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      font-size: 0.95rem;
    }

    th {
      background-color: #2563eb;
      color: white;
      text-align: left;
      padding: 12px;
      font-weight: 600;
      font-size: 14px;
    }

    td {
      padding: 12px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 14px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    tr:hover {
      background-color: #f3f4f6;
    }

    /* Keep layout stable and aligned */
    .stats {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .card {
      flex: 1;
      min-width: 120px;
      background: #2563eb;
      color: #fff;
      text-align: center;
      padding: 15px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
    }

    .card:hover {
      transform: translateY(-3px);
    }

    .card span {
      display: block;
      margin-top: 5px;
      font-size: 1.2rem;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      table {
        font-size: 0.85rem;
      }
      th, td {
        padding: 10px;
      }
    }
  </style>
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
       <li><a href="#" onclick="confirmLogout(event)">🚪 Logout</a></li>

      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header>
        <h1>Hello, <?php echo htmlspecialchars($_SESSION['uname']); ?> 👋</h1>
      </header>

      <!-- Stats Cards -->
      <section class="stats">
        <div class="card">Users <span><?php echo $users; ?></span></div>
        <div class="card">Posts <span><?php echo $posts; ?></span></div>
        <div class="card">Comments <span><?php echo $comments; ?></span></div>
        <div class="card">Likes <span><?php echo $likes; ?></span></div>
      </section>

      <!-- Tables stacked vertically -->
      <section class="dashboard-wrapper">
        <div class="recent-box">
          <h3>Recent Users</h3>
          <table>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Joined</th>
            </tr>
            <?php while ($u = mysqli_fetch_assoc($recent_users)) { ?>
              <tr>
                <td><?php echo htmlspecialchars($u['uname']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo $u['created_at']; ?></td>
              </tr>
            <?php } ?>
          </table>
        </div>

        <div class="recent-box">
          <h3>Recent Posts</h3>
          <table>
            <tr>
              <th>Title</th>
              <th>Date</th>
            </tr>
            <?php while ($p = mysqli_fetch_assoc($recent_posts)) { ?>
              <tr>
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <td><?php echo $p['created_at']; ?></td>
              </tr>
            <?php } ?>
          </table>
        </div>
      </section>
    </main>
  </div>
  <script>
function confirmLogout(event) {
    event.preventDefault(); // stop the default link action
    const confirmLogout = confirm("Are you sure you want to log out?");
    if (confirmLogout) {
        window.location.href = "../logout.php"; // perform logout
    }
    // else do nothing (stay on the current page)
}
</script>

</body>
</html>
