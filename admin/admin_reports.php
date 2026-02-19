<?php
// admin_reports.php
include "config.php";
session_start();

// Only admin allowed
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$roleRes = mysqli_query($conn, "SELECT user_id, role FROM credentials WHERE email='$email' LIMIT 1");
$roleRow = mysqli_fetch_assoc($roleRes);
if (!$roleRow || $roleRow['role'] !== 'admin') {
    echo "Access denied";
    exit();
}

// Fetch reports with reporter/reported and optional target previews
$sql = "
SELECT r.*, 
       reporter.uname AS reporter_name,
       reported.uname AS reported_name,
       p.title AS post_title,
       c.comment AS comment_text
FROM reports r
LEFT JOIN credentials reporter ON r.reporter_id = reporter.user_id
LEFT JOIN credentials reported ON r.reported_user_id = reported.user_id
LEFT JOIN posts p ON (r.target_type = 'post' AND p.post_id = r.target_id)
LEFT JOIN comments c ON (r.target_type = 'comment' AND c.comment_id = r.target_id)
ORDER BY r.status ASC, r.created_at DESC
";
$res = mysqli_query($conn, $sql);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin - Reports</title>
  <style>
    body{font-family: Arial; padding:20px;}
    table{width:100%; border-collapse: collapse;}
    th,td{padding:8px;border:1px solid #ddd; text-align:left;}
    tr.open{background:#fff;}
    tr.closed{background:#f7f7f7;color:#666;}
    .btn{padding:6px 10px; margin-right:6px; cursor:pointer;}
    .btn-danger{background:#e74c3c;color:#fff;border:0;}
    .btn-warning{background:#f39c12;color:#fff;border:0;}
    .btn-ok{background:#2ecc71;color:#fff;border:0;}
    .small{font-size:12px;color:#666;}
    textarea{width:100%; min-height:60px;}
  </style>
</head>
<body>
  <h2>Reported Items</h2>
  <p class="small">Review reports and take action. Actions produce admin notifications to affected users and close the report.</p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Reporter</th>
        <th>Reported User</th>
        <th>Target</th>
        <th>Reason</th>
        <th>Created</th>
        <th>Status</th>
        <th>Admin Note & Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($res && mysqli_num_rows($res) > 0): 
        while ($row = mysqli_fetch_assoc($res)):
          $rid = (int)$row['report_id'];
          $statusClass = ($row['status'] === 'open') ? 'open' : 'closed';
    ?>
      <tr id="report-row-<?php echo $rid; ?>" class="<?php echo $statusClass; ?>">
        <td><?php echo $rid; ?></td>
        <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
        <td><?php echo htmlspecialchars($row['reported_name']); ?></td>
        <td>
          <strong><?php echo htmlspecialchars($row['target_type']); ?></strong>
          <?php if ($row['target_type'] === 'post' && $row['post_title']) {
            echo "<div class='small'>Post: " . htmlspecialchars($row['post_title']) . "</div>";
          } elseif ($row['target_type'] === 'comment' && $row['comment_text']) {
            echo "<div class='small'>Comment: " . htmlspecialchars(substr($row['comment_text'],0,140)) . "</div>";
          } ?>
          <div class='small'>ID: <?php echo (int)$row['target_id']; ?></div>
        </td>
        <td><?php echo nl2br(htmlspecialchars($row['reason'])); ?></td>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        <td id="status-<?php echo $rid; ?>"><?php echo htmlspecialchars($row['status']); ?></td>
        <td>
          <textarea id="note-<?php echo $rid; ?>" placeholder="Admin note (reason, penalty duration, etc.)"><?php echo htmlspecialchars($row['admin_note']); ?></textarea>
          <div style="margin-top:6px;">
            <!-- buttons -->
            <?php if ($row['status'] === 'open'): ?>
              <button class="btn btn-ok" onclick="takeAction(<?php echo $rid; ?>, 'warn')">Warn</button>
              <button class="btn btn-warning" onclick="takeAction(<?php echo $rid; ?>, 'suspend_post')">Suspend Post</button>
              <button class="btn btn-warning" onclick="takeAction(<?php echo $rid; ?>, 'suspend_comment')">Suspend Comment</button>
              <button class="btn btn-danger" onclick="takeAction(<?php echo $rid; ?>, 'suspend_account')">Suspend Account</button>
              <button class="btn" onclick="takeAction(<?php echo $rid; ?>, 'dismiss')">Dismiss</button>
            <?php else: ?>
              <div class="small">Actioned: <?php echo htmlspecialchars($row['admin_action']); ?><br>Note: <?php echo htmlspecialchars($row['admin_note']); ?></div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="8">No reports found</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

<script>
async function takeAction(reportId, action) {
  let note = document.getElementById('note-' + reportId).value || '';
  if (!confirm('Are you sure? Action: ' + action)) return;

  let payload = { report_id: reportId, action: action, admin_note: note };
  try {
    let res = await fetch('admin_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    let data = await res.json();
    if (data.success) {
      alert(data.msg);
      // update UI: status and admin_action/note
      document.getElementById('status-' + reportId).innerText = 'closed';
      let row = document.getElementById('report-row-' + reportId);
      if (row) row.className = 'closed';
      // show admin action & note inline
      // simple refresh of the action cell to show closed state:
      // replace the action cell content:
      let cell = document.getElementById('report-row-' + reportId).children[7];
      if (cell) {
        cell.innerHTML = "<div class='small'>Actioned: " + (action) + "<br>Note: " + (note || '-') + "</div>";
      }
    } else {
      alert('Error: ' + data.msg);
    }
  } catch (err) {
    console.error(err);
    alert('Server error');
  }
}
</script>
</body>
</html>
