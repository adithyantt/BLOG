<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];
$userRes = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$user = mysqli_fetch_assoc($userRes);
$user_id = $user['user_id'];

/* -------- Handle AJAX Requests -------- */
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($_POST['action'] === "mark_one" && isset($_POST['id'])) {
        $notif_id = (int) $_POST['id'];
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id");
        echo "success";
        exit();
    }

    if ($_POST['action'] === "mark_all") {
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
        echo "success";
        exit();
    }

    if ($_POST['action'] === "fetch_new") {
        $result = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        header("Content-Type: application/json");
        echo json_encode($data);
        exit();
    }
}

// Fetch notification
$query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Helper: format time ago
function timeAgo($time) {
    $ts = strtotime($time);
    $diff = time() - $ts;

    if ($diff < 60) return "just now";
    $mins = floor($diff / 60);
    if ($mins < 60) return "$mins min ago";
    $hrs = floor($mins / 60);
    if ($hrs < 24) return "$hrs hr ago";
    $days = floor($hrs / 24);
    return "$days day" . ($days > 1 ? "s" : "") . " ago";
}

// Helper: icon by type
function getIcon($msg) {
    if (stripos($msg, "suspend") !== false) return "🚫";
    if (stripos($msg, "warn") !== false) return "⚠️";
    if (stripos($msg, "comment") !== false) return "💬";
    if (stripos($msg, "like") !== false) return "❤️";
    if (stripos($msg, "follow") !== false) return "👤";
    return "🔔";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; background:#f9fafb; }
    h2 { margin-bottom: 10px; }
    .notif-controls { margin-bottom: 15px; }
    .notif-card { padding: 12px; border-radius: 6px; margin-bottom: 10px; display:flex; align-items:center; }
    .notif-card.unread { background:#eef6ff; font-weight:bold; }
    .notif-card.read { background:#fff; color:#555; border:1px solid #eee; }
    .notif-icon { font-size:20px; margin-right:10px; }
    .notif-time { font-size:12px; color:gray; margin-left:auto; }
    .mark-read-btn { margin-left: 10px; padding: 3px 6px; cursor:pointer; font-size:12px; }
    a.notif-link { color: inherit; text-decoration: none; flex:1; }
    a.notif-link:hover { text-decoration: underline; }
    button { padding:6px 10px; border:none; border-radius:4px; cursor:pointer; }
    #mark-all { background:#2563eb; color:white; }
    #refresh { background:#10b981; color:white; margin-left:10px; }
  </style>
</head>

<body>
  <!-- Back to Home -->
  <div style="margin-bottom:20px;">
    <a href="home.php" style="
        display:inline-block;
        padding:8px 12px;
        background:black;
        color:white;
        border-radius:6px;
        text-decoration:none;
        font-weight:600;
    ">← Back to Home</a>
  </div>

  <h2>🔔 Notifications</h2>
  <div class="notif-controls">
    <button id="mark-all">Mark All as Read</button>
    <button id="refresh">Refresh</button>
  </div>

  <div id="notif-list">
    <?php if ($result && mysqli_num_rows($result) > 0) { 
        while ($row = mysqli_fetch_assoc($result)) { ?>
      <div class="notif-card <?php echo $row['is_read'] ? 'read' : 'unread'; ?>" data-id="<?php echo $row['id']; ?>">
        <div class="notif-icon"><?php echo getIcon($row['message']); ?></div>
        <a class="notif-link" href="<?php echo $row['link'] ? $row['link'] : '#'; ?>" 
           onclick="handleNotifClick(event, <?php echo $row['id']; ?>, this.parentElement);">
           <?php echo htmlspecialchars($row['message']); ?>
        </a>
        <span class="notif-time"><?php echo timeAgo($row['created_at']); ?></span>
        <?php if (!$row['is_read']) { ?>
          <button class="mark-read-btn">Mark</button>
        <?php } ?>
      </div>
    <?php } } else { ?>
      <p>No notifications found</p>
    <?php } ?>
  </div>

<script>
// Mark single notification as read
function markRead(id, notifItem) {
    fetch("notifications.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=mark_one&id=" + id
    })
    .then(res => res.text())
    .then(data => {
        if (data === "success") {
            notifItem.classList.remove("unread");
            notifItem.classList.add("read");
            notifItem.querySelector(".mark-read-btn")?.remove();

            let countEl = document.getElementById("notif-count");
            if (countEl) {
                let current = parseInt(countEl.innerText) || 0;
                if (current > 0) countEl.innerText = current - 1;
            }
        }
    });
}

// Handle notification link click
function handleNotifClick(e, id, notifItem) {
    e.preventDefault();
    let targetUrl = notifItem.querySelector(".notif-link").getAttribute("href");
    markRead(id, notifItem);
    setTimeout(() => {
        if (targetUrl && targetUrl !== "#") window.location.href = targetUrl;
    }, 200);
}

// Manual mark button
document.querySelectorAll(".mark-read-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        let notifItem = this.closest(".notif-card");
        markRead(notifItem.dataset.id, notifItem);
    });
});

// Mark all
document.getElementById("mark-all").addEventListener("click", () => {
    fetch("notifications.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=mark_all"
    })
    .then(res => res.text())
    .then(data => {
        if (data === "success") {
            document.querySelectorAll(".notif-card").forEach(el => {
                el.classList.remove("unread");
                el.classList.add("read");
                el.querySelector(".mark-read-btn")?.remove();
            });
            let countEl = document.getElementById("notif-count");
            if (countEl) countEl.innerText = "0";
        }
    });
});

// Refresh
document.getElementById("refresh").addEventListener("click", () => {
    fetch("notifications.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=fetch_new"
    })
    .then(res => res.json())
    .then(data => {
        let list = document.getElementById("notif-list");
        list.innerHTML = "";
        if (data.length > 0) {
            data.forEach(n => {
                let div = document.createElement("div");
                div.className = "notif-card " + (n.is_read == 0 ? "unread" : "read");
                div.dataset.id = n.id;
                div.innerHTML = `
                    <div class="notif-icon">🔔</div>
                    <a class="notif-link" href="${n.link ? n.link : "#"}"
                       onclick="handleNotifClick(event, ${n.id}, this.parentElement);">
                       ${n.message}
                    </a>
                    <span class="notif-time">${n.created_at}</span>
                    ${n.is_read == 0 ? '<button class="mark-read-btn">Mark</button>' : ''}
                `;
                list.appendChild(div);
            });
        } else {
            list.innerHTML = "<p>No notifications found</p>";
        }
    });
});
</script>
</body>
</html>
