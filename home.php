<?php
include "config.php";
session_start();

// ----------------- User login check -----------------
if (empty($_SESSION['email']) || empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$email   = $_SESSION['email'];

// ----------------- Fetch user info -----------------
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img, status, is_suspended FROM credentials WHERE user_id = $user_id LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);

if (!$currentUser) {
    header("Location: login.html"); // safer than destroying session
    exit();
}

// ----------------- Suspended account check -----------------
$status = $currentUser['status'] ?? 'active';
$isSuspended = (int)($currentUser['is_suspended'] ?? 0);

if ($status === 'suspended' || $isSuspended === 1) {
    session_destroy();
    die("Your account has been suspended by admin.");
}

// ----------------- Current user info -----------------
$current_user_id = (int)$currentUser['user_id'];
$uname           = $currentUser['uname'];
$profile_img     = !empty($currentUser['profile_img']) ? $currentUser['profile_img'] : "uploads/default_profile.png";

// ----------------- Notification count -----------------
$notif_count = 0;
$notifRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = $current_user_id AND is_read = 0");
if ($notifRes) {
    $notif_count = (int)mysqli_fetch_assoc($notifRes)['cnt'];
}

/* -- Helper: get post meta -- */
function getPostMeta($conn, $post_id, $current_user_id) {
  $post_id = (int)$post_id;

  $likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id"))['c'] ?? 0;
  $comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM comments WHERE post_id=$post_id"))['c'] ?? 0;

  $liked = 0;
  $bookmarked = 0;
  if ($current_user_id) {
    $liked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id AND user_id=$current_user_id"))['c'] ?? 0;
    $bookmarked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookmarks WHERE post_id=$post_id AND user_id=$current_user_id"))['c'] ?? 0;
  }

  return [
    'like_count' => (int)$likes,
    'comment_count' => (int)$comments,
    'liked' => $liked ? 1 : 0,
    'bookmarked' => $bookmarked ? 1 : 0
  ];
}

function fetchPostsForFeed($conn, $sql, $current_user_id, $email) {
    $result = mysqli_query($conn, $sql);
    $html = "";

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {

            // --- Post meta ---
            $likes = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id={$row['post_id']}"))['c'] ?? 0);
            $comments = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM comments WHERE post_id={$row['post_id']}"))['c'] ?? 0);
            $views = (int)($row['views'] ?? 0);

            $liked = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id={$row['post_id']} AND user_id=$current_user_id"))['c'] ?? 0);
            $bookmarked = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookmarks WHERE post_id={$row['post_id']} AND user_id=$current_user_id"))['c'] ?? 0);

            // --- Ownership & follow ---
            $isOwner = ($row['email'] === $email);
            $isFollowing = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id={$row['user_id']}"))['c'] ?? 0);
            $followLabel = $isFollowing ? "Unfollow" : "Follow";

            // --- Profile & Thumbnail ---
            $profileImg = !empty($row['profile_img']) ? htmlspecialchars($row['profile_img']) : "uploads/default_profile.png";
            $thumb = !empty($row['img_url']) ? "<div class='post-thumbnail'><img src='".htmlspecialchars($row['img_url'])."' alt='Thumbnail'></div>" : "";

            // --- Safe fields ---
            $safeTitle = htmlspecialchars($row['title']);
            $safeEmail = htmlspecialchars($row['email']);
            $safeDate  = htmlspecialchars($row['created_at']);
            $safeName  = htmlspecialchars($row['uname']);
            $safeExcerpt = nl2br(substr(htmlspecialchars($row['content']),0,120));
            $safeCategory = !empty($row['category']) ? htmlspecialchars($row['category']) : "General";

            $likeCls = $liked ? "icon liked" : "icon";
            // Use absolute path to assets folder to ensure icons always show
            $bmIconSrc = $bookmarked ? "assets/bookmark-filled.svg" : "assets/bookmark-outline.svg";

            $html .= "
            <div class='post-card' data-post-id='{$row['post_id']}' data-user-id='{$row['user_id']}'>
                <div class='post-content'>
                    <div class='post-meta'>
                        <img src='{$profileImg}' alt='Profile'>
                       <strong>{$safeName}</strong> | {$safeDate}
                    </div>

                    <h3 class='post-title'>{$safeTitle}</h3>
                    <span class='topic-pill'>{$safeCategory}</span>
                    <p class='post-excerpt'>{$safeExcerpt}...</p>

                    <div class='post-actions'>
                        <button class='{$likeCls}' data-action='like'>
                            <span class='ico'>&#10084;</span> <span class='count like-count'>{$likes}</span>
                        </button>

                        <a class='icon' href='view.php?pid={$row['post_id']}#comments'>
                            <span class='ico'>&#128172;</span> <span class='count'>{$comments}</span>
                        </a>

                        <span class='post-views'>👁️ {$views}</span>

                        

                        <a class='read-link' href='view.php?pid={$row['post_id']}'>Read</a>
                    </div>
                </div>
                {$thumb}

                <div class='menu-wrap'>
                    <button class='more-btn' aria-expanded='false' title='More'>⋯</button>
                    <div class='more-menu' role='menu'>
                        <button class='menu-item' data-action='report'>Report post</button>
                        ".(!$isOwner ? "<button class='menu-item' data-action='follow'>{$followLabel}</button>" : "")."
                        ".($isOwner ? "<a class='menu-item' href='edit.php?pid={$row['post_id']}'>Edit</a>" : "")."
                        ".($isOwner ? "<a class='menu-item danger' href='delete.php?pid={$row['post_id']}' onclick=\"return confirm('Delete this post?');\">Delete</a>" : "")."
                    </div>
                </div>
            </div>";
        }
    } else {
        $html = "<p>No posts found.</p>";
    }

    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NoCap Press - Feed</title>
  <link rel="stylesheet" href="home.css">
</head>
<body>

<!-- Site Title (Top Left Corner) -->
<div class="brand-title">NoCapPress</div>

<!-- Sidebar -->
<nav class="side-nav">
  
  <div class="menu-item" data-toggle="home-submenu"><span>Home</span><span class="arrow">></span></div>
  <div id="home-submenu" class="submenu">
    <a href="#" data-target="for-you-tab">For You</a>
    <a href="#" data-target="following-tab">Following</a>
    <a href="#" data-target="trending-tab">Trending</a>
  </div>
  <a href="search.php" class="menu-item">Search</a>
  <a href="create_blog.php" class="menu-item">Create</a>
  <a href="bookmarks.php" class="menu-item">Bookmark</a>
  <a href="profile.php" class="menu-item">Profile</a>
</nav>

<!-- Header -->
<div class="top-header">
  <!-- Notifications Bell -->
  <a href="notifications.php" style="text-decoration:none;color:inherit;">
    <div id="notif-bell" style="position:relative;display:inline-block;">
      🔔
      <span id="notif-count" style="position:absolute;top:-5px;right:-10px;background:red;color:white;border-radius:50%;padding:2px 6px;font-size:12px;">
        <?php echo (int)$notif_count; ?>
      </span>
    </div>
  </a>

  <!-- User info -->
  <div class="profile-icon" style="display:flex;align-items:center;gap:8px;">
    <span style="font-size:15px;font-weight:600;"><?php echo htmlspecialchars($uname); ?></span>
    <a href="profile.php">
      <img src="<?php echo $profile_img; ?>" alt="Profile" style="width:32px;height:32px;border-radius:50%;">
    </a>
  </div>
</div>


<!-- Content -->
<div class="main-content">
  <section id="home-section" class="spa-section active">
    <div id="for-you-tab" class="tab-content active">
      <h2>For You</h2><hr>
      <?php
      $sql_for_you = "SELECT p.*, c.email,c.uname, c.profile_img
                FROM posts p
                JOIN credentials c ON p.user_id = c.user_id
                WHERE p.status='active'
                ORDER BY p.created_at DESC";

      echo fetchPostsForFeed($conn, $sql_for_you, $current_user_id, $email);
      ?>
    </div>

    <div id="following-tab" class="tab-content">
      <h2>Following</h2><hr>
      <?php
      $sql_following = "SELECT p.*, c.email,c.uname, c.profile_img
                  FROM posts p
                  JOIN credentials c ON p.user_id = c.user_id
                  WHERE p.user_id IN (
                      SELECT following_id FROM follows WHERE follower_id=$current_user_id
                  )
                  AND p.status='active'
                  ORDER BY p.created_at DESC";

      echo fetchPostsForFeed($conn, $sql_following, $current_user_id, $email);
      ?>
    </div>

    <div id="trending-tab" class="tab-content">
      <h2>🔥 Trending</h2><hr>
      <?php
       $sql_trending = "SELECT p.*, c.email,c.uname,c.profile_img, COUNT(l.like_id) AS like_count
                 FROM posts p
                 JOIN credentials c ON p.user_id = c.user_id
                 LEFT JOIN likes l ON p.post_id = l.post_id
                 WHERE p.status='active'
                 GROUP BY p.post_id
                 ORDER BY like_count DESC, p.created_at DESC
                 LIMIT 10";

      echo fetchPostsForFeed($conn, $sql_trending, $current_user_id, $email);
      ?>
    </div>
  </section>
</div>

<!-- Report Modal -->
<div id="report-modal" role="dialog" aria-modal="true" aria-labelledby="report-title" style="display:none;">
  <div class="modal-box">
    <h3 id="report-title">Report Post</h3>
    <label for="report-reason">Reason</label>
    <select id="report-reason">
      <option value="">Select reason</option>
      <option value="spam">Spam</option>
      <option value="abuse">Abusive or harmful content</option>
      <option value="misinfo">Misinformation</option>
      <option value="plagiarism">Plagiarism</option>
      <option value="other">Other</option>
    </select>
    <label for="report-text" style="margin-top:8px;display:block;">Additional details (optional)</label>
    <textarea id="report-text" placeholder="Describe the issue..."></textarea>
    <div class="actions">
      <button id="report-cancel">Cancel</button>
      <button id="report-submit" class="primary">Submit</button>
    </div>
  </div>
</div>

<!-- ===== Inline JS (cleaned) ===== -->
<script>
/* ---------- Sidebar submenu toggle ---------- */
document.querySelectorAll(".side-nav .menu-item[data-toggle]").forEach(item => {
  item.addEventListener("click", () => {
    item.classList.toggle("open");
    const submenuId = item.dataset.toggle;
    const submenu = document.getElementById(submenuId);
    if (!submenu) return;
    submenu.style.display = submenu.style.display === "flex" ? "none" : "flex";
  });
});

/* ---------- Home tabs switching ---------- */
document.querySelectorAll(".submenu a[data-target]").forEach(link => {
  link.addEventListener("click", (e) => {
    e.preventDefault();
    const target = link.dataset.target;
    if (!target) return;

    document.querySelectorAll(".submenu a").forEach(a=>a.classList.remove("active-link"));
    link.classList.add("active-link");

    document.querySelectorAll(".tab-content").forEach(s => s.classList.remove("active"));

    const tabEl = document.getElementById(target);
    if (tabEl) tabEl.classList.add("active");
  });
});

/* ---------- Post interactivity ---------- */
function attachPostInteractivity(scope=document) {
  // like
  scope.querySelectorAll(".post-card [data-action='like']").forEach(btn => {
    btn.addEventListener("click", async () => {
      const card = btn.closest(".post-card");
      const postId = card.dataset.postId;
      try {
        const res = await fetch("like.php", {
          method: "POST",
          headers: {"Content-Type":"application/json"},
          body: JSON.stringify({ post_id: postId })
        });
        const data = await res.json();
        if (data.success) {
          card.querySelector(".like-count").textContent = data.like_count;
          btn.classList.toggle("liked", !!data.liked);
        }
      } catch(e) { console.error(e); }
    });
  });

  // bookmark toggle (call inside attachPostInteractivity)
scope.querySelectorAll(".post-card [data-action='bookmark']").forEach(btn => {
    btn.addEventListener("click", async () => {
        const card = btn.closest(".post-card");
        const postId = card.dataset.postId;
        const img = btn.querySelector(".bm-icon");
        if (!img) return;

        try {
            const res = await fetch("bookmark.php", {
                method: "POST",
                headers: {"Content-Type":"application/json"},
                body: JSON.stringify({ post_id: postId })
            });
            const data = await res.json();
            if (data.success) {
                // ensure path matches exactly assets folder
                img.src = data.bookmarked ? "assets/bookmark-filled.svg" : "assets/bookmark-outline.svg";
            }
        } catch(e) {
            console.error("Bookmark toggle error:", e);
        }
    });
});


  // three-dot toggle
  scope.querySelectorAll(".post-card .more-btn").forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      document.querySelectorAll(".more-menu").forEach(m => {
        if (m !== menu) m.style.display = "none";
      });
      menu.style.display = (menu.style.display === "flex" ? "none" : "flex");
    });
  });

  // follow/report
  scope.querySelectorAll(".post-card .more-menu .menu-item").forEach(mi => {
    mi.addEventListener("click", async (e) => {
      const el = e.currentTarget;
      const action = el.dataset.action;
      const card = el.closest(".post-card");
      const postId = card.dataset.postId;
      const authorId = card.dataset.userId;

      if (action === "follow") {
        try {
          const res = await fetch("follow.php", {
            method: "POST",
            headers: {"Content-Type":"application/json"},
            body: JSON.stringify({ user_id: authorId })
          });
          const data = await res.json();
          if (data.success) {
            el.textContent = data.following ? "Unfollow" : "Follow";
          }
        } catch(e) { console.error(e); }
      }

      if (action === "report") {
        openReportModal(postId);
      }

      el.closest(".more-menu").style.display = "none";
    });
  });
}
attachPostInteractivity(document);

// close menus outside
document.addEventListener("click", () => {
  document.querySelectorAll(".more-menu").forEach(m => m.style.display = "none");
});

/* ---------- Report modal ---------- */
let currentReportPostId = null;

// Open the modal
function openReportModal(postId) {
  currentReportPostId = postId;
  document.getElementById("report-reason").value = ""; // reset
  document.getElementById("report-text").value = "";   // reset
  document.getElementById("report-modal").style.display = "block";
}

// Close the modal
function closeReportModal() {
  document.getElementById("report-modal").style.display = "none";
}

// Cancel button
document.getElementById("report-cancel")?.addEventListener("click", closeReportModal);

// Submit button
document.getElementById("report-submit")?.addEventListener("click", async () => {
  const reason = document.getElementById("report-reason").value.trim();
  const details = document.getElementById("report-text").value.trim();

  if (!reason) {
    alert("⚠️ Please select a reason for reporting.");
    return;
  }

  if (!currentReportPostId) {
    alert("⚠️ Post ID missing. Cannot report.");
    return;
  }

  try {
    const res = await fetch("report_post.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ post_id: currentReportPostId, reason, details }),
      credentials: "include" // important to send session cookie
    });

    const text = await res.text();
    console.log("Raw response from report_post.php:", text); // debug

    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      alert("⚠️ Invalid server response. Check console for details.");
      console.error("JSON parse error:", err);
      return;
    }

    if (data.success) {
      alert("✅ " + (data.msg || "Report submitted successfully."));
      closeReportModal();
    } else {
      alert("❌ " + (data.msg || "Failed to submit report."));
    }

  } catch (err) {
    alert("⚠️ Network error: " + err.message);
    console.error(err);
  }
});


/* ---------- Notification count ---------- */
async function loadNotificationCount() {
  const notifEl = document.getElementById('notif-count');
  if (!notifEl) return;
  try {
    const res = await fetch('fetch_unread_notifications.php');
    const data = await res.json();
    if (data.success) {
      if ((data.unread_count || 0) > 0) {
        notifEl.textContent = data.unread_count;
        notifEl.style.display = 'inline-block';
      } else {
        notifEl.style.display = 'none';
      }
    }
  } catch(e) {}
}
document.addEventListener('DOMContentLoaded', loadNotificationCount);
</script>

</body>
</html>

