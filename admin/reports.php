<?php
include "../config.php";
session_start();

// Only admin or superadmin can access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: ../login.php");
    exit();
}

// Fetch all reports
$sql = "
    SELECT r.*,
           u.uname AS reporter_name,
           ru.uname AS reported_user,
           p.title AS post_title,
           c.comment AS comment_text,
           p.status AS post_status,
           c.status AS comment_status,
           ru.status AS account_status
    FROM reports r
    JOIN credentials u ON r.reporter_id = u.user_id
    LEFT JOIN credentials ru ON r.reported_user_id = ru.user_id
    LEFT JOIN posts p ON r.reported_post_id = p.post_id
    LEFT JOIN comments c ON r.reported_comment_id = c.comment_id
    ORDER BY r.created_at DESC
";
$reports = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<style>
/* ====== PAGE LAYOUT ====== */
.admin-container {
    display: flex;
    height: 100vh;
    background-color: #f9fafb;
    font-family: "Poppins", sans-serif;
}

.main-content {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
}

/* ===== HEADER ===== */
header h1 {
    font-size: 26px;
    color: #111827;
    margin-bottom: 25px;
}

/* ===== TABLE STYLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
    vertical-align: top;
}

/* ===== BUTTONS ===== */
.action-btn {
    padding: 6px 10px;
    margin: 2px;
    border-radius: 6px;
    font-size: 13px;
    border: none;
    color: white;
    display: inline-block;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.warn { background-color: #f59e0b; }
.suspend { background-color: #ef4444; }
.unsuspend { background-color: #22c55e; }
.dismiss { background-color: #6b7280; }

.action-btn:hover { opacity: 0.9; transform: scale(1.02); }

/* ===== DETAILS TOGGLE ===== */
.details {
    display: none;
    margin-top: 8px;
    padding: 10px;
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.toggle-btn {
    background-color: #2563eb;
    color: white;
    padding: 6px 8px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    margin-left: 5px;
}

.meta-badge {
    display: inline-block;
    background: #e5e7eb;
    color: #111827;
    border-radius: 999px;
    font-size: 12px;
    padding: 3px 8px;
    margin-left: 6px;
}

/* ===== STATUS ===== */
.status-pending { color: #dc2626; font-weight: 600; }
.status-reviewed { color: #16a34a; font-weight: 600; }

/* ===== ADMIN NOTE ===== */
textarea.admin-note {
    width: 100%;
    min-height: 50px;
    padding: 8px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    margin-top: 6px;
    font-size: 14px;
    outline: none;
}
textarea.admin-note:focus { border-color: #2563eb; }

/* ===== SMALL TEXT ===== */
.small { font-size: 12px; color: #6b7280; }

/* ===== NO DATA ===== */
.no-data {
    text-align: center;
    color: #888;
    padding: 20px;
    font-style: italic;
}
</style>
</head>
<body>
<div class="admin-container">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <h2>NoCapPress Admin</h2>
    <ul>
      <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
      <li><a href="manage_users.php">👤 Manage Users</a></li>
      <li><a href="manage_posts.php">📝 Manage Posts</a></li>
      <li><a href="reports.php" class="active">⚠️ Reports</a></li>
      <li><a href="#" onclick="confirmLogout(event)">🚪 Logout</a></li>

    </ul>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <header><h1>Reports</h1></header>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Reporter</th>
          <th>Reported User</th>
          <th>Reported Item</th>
          <th>Reason</th>
          <th>Status</th>
          <th>Admin Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($reports && mysqli_num_rows($reports) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($reports)): ?>
                <tr id="report-<?php echo $r['report_id']; ?>">
                    <td>#<?php echo $r['report_id']; ?><br><span class="small"><?php echo $r['created_at']; ?></span></td>
                    <td><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['reported_user'] ?? '—'); ?><br>
                        <span class="small">Status: <?php echo $r['account_status'] ?? 'active'; ?></span>
                    </td>
                    <td>
                        <?php if ($r['post_title']): ?>
                            <strong>Post:</strong> <?php echo htmlspecialchars($r['post_title']); ?>
                            <span class="meta-badge"><?php echo $r['post_status'] ?? 'active'; ?></span>
                            <button class="toggle-btn" onclick="toggleDetails(<?php echo $r['report_id']; ?>)">View</button>
                            <div id="details-<?php echo $r['report_id']; ?>" class="details">
                                <?php echo nl2br(htmlspecialchars($r['post_content'] ?? '')); ?>
                            </div>
                        <?php elseif ($r['comment_text']): ?>
                            <strong>Comment:</strong>
                            <span class="meta-badge"><?php echo $r['comment_status'] ?? 'active'; ?></span>
                            <button class="toggle-btn" onclick="toggleDetails(<?php echo $r['report_id']; ?>)">View</button>
                            <div id="details-<?php echo $r['report_id']; ?>" class="details">
                                <?php echo nl2br(htmlspecialchars($r['comment_text'])); ?>
                            </div>
                        <?php else: ?>
                            Account Report
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['reason']); ?></td>
                    <td id="row-status-<?php echo $r['report_id']; ?>">
                        <?php if ($r['status'] === 'pending'): ?>
                            <span class="status-pending">Pending</span>
                        <?php else: ?>
                            <span class="status-reviewed">Reviewed</span>
                        <?php endif; ?>
                    </td>
                    <td id="actions-<?php echo $r['report_id']; ?>">
                        <?php if ($r['status'] === 'pending'): ?>
                            <textarea id="note-<?php echo $r['report_id']; ?>" class="admin-note" placeholder="Add admin note..."></textarea>
                            <div class="action-container">
                                <button class="action-btn warn" onclick="takeAction(<?php echo $r['report_id']; ?>, 'warn')">Warn</button>
                                <button class="action-btn suspend" onclick="takeAction(<?php echo $r['report_id']; ?>, 'suspend')">Suspend</button>
                                <button class="action-btn dismiss" onclick="takeAction(<?php echo $r['report_id']; ?>, 'dismiss')">Dismiss</button>
                            </div>
                        <?php else: ?>
                            <div><strong>Action:</strong> <?php echo htmlspecialchars($r['admin_action'] ?? '-'); ?><br>
                            <strong>Note:</strong> <?php echo htmlspecialchars($r['admin_note'] ?? '-'); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" class="no-data">No reports found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<script>
function toggleDetails(id) {
  const el = document.getElementById('details-' + id);
  if (el) el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
function takeAction(id, action) {
  alert("Action '" + action + "' triggered for Report #" + id + ". (AJAX handling same as manage_posts)");
}

function confirmLogout(e) {
  e.preventDefault(); // prevent immediate logout
  if (confirm("Are you sure you want to log out of the admin panel?")) {
    window.location.href = "../logout.php";
  }
}
</script>

</body>
</html>
