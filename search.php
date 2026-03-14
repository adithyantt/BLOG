<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];

// Current user info of user
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;
$currentUserName = $currentUser['uname'];
$currentUserImg = !empty($currentUser['profile_img'])
    ? (strpos($currentUser['profile_img'], 'uploads/') === false ? 'uploads/' . $currentUser['profile_img'] : $currentUser['profile_img'])
    : "uploads/default_profile.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NoCap Press - Search</title>
<link rel="stylesheet" href="home.css">
<style>
.search-container { margin: 40px auto 20px; max-width: 600px; text-align: center; }
.search-bar { width: 100%; padding: 14px 20px; font-size: 18px; border-radius: 30px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.08); outline: none; }
.tabs { display: flex; justify-content: center; margin-top: 20px; border-bottom: 1px solid #ddd; }
.tab { padding: 10px 20px; cursor: pointer; font-weight: 500; color: #555; }
.tab.active { border-bottom: 2px solid #000; color: #000; }
.results { margin-top: 20px; max-width: 800px; margin-left: auto; margin-right: auto; }
.category-item { padding: 12px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background:#fafafa; display:flex; justify-content:space-between; align-items:center; }
.category-item button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; background:#0d6efd; color:white; }
.user-card { display:flex; align-items:center; gap:15px; padding:12px; border-bottom:1px solid #eee; cursor:pointer; }
.user-card img { width:50px; height:50px; border-radius:50%; object-fit:cover; }
.user-card button.follow-btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; background:#0d6efd; color:white; }
.post-meta strong { margin-right:6px; }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="side-nav">
  <div class="brand-title">NoCapPress</div>
  <a href="home.php" class="menu-item">Home</a>
  <a href="search.php" class="menu-item active-link">Search</a>
  <a href="create_blog.php" class="menu-item">Create</a>
  <a href="bookmarks.php" class="menu-item">Bookmark</a>
  <a href="profile.php" class="menu-item">Profile</a>
</nav>

<!-- Top Header -->
<div class="top-header">
  <div style="display:flex;align-items:center;gap:10px;">
    <img src="<?= $currentUserImg ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;">
    <span><?= htmlspecialchars($currentUserName) ?></span>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="search-container">
    <input type="text" id="searchInput" class="search-bar" placeholder="Search posts, users, or categories...">
  </div>

  <div class="tabs">
    <div id="posts-tab" class="tab active">Posts</div>
    <div id="users-tab" class="tab">Users</div>
    <div id="categories-tab" class="tab">Categories</div>
  </div>

  <div class="results">
    <div id="posts-results"></div>
    <div id="users-results" style="display:none;"></div>
    <div id="categories-results" style="display:none;"></div>
  </div>
</div>

<script>
const searchInput = document.getElementById("searchInput");
const postsResults = document.getElementById("posts-results");
const usersResults = document.getElementById("users-results");
const categoriesResults = document.getElementById("categories-results");

function search(type, overrideQuery="") {
  const q = overrideQuery || searchInput.value.trim();
  if (!q) {
    if(type==='posts') postsResults.innerHTML = "";
    if(type==='users') usersResults.innerHTML = "";
    if(type==='categories') categoriesResults.innerHTML = "";
    return;
  }

  const url = type==='users' ? "search_user_backend.php?q=" : "search_backend.php?type=" + type + "&q=";
  fetch(url + encodeURIComponent(q))
    .then(res => res.text())
    .then(html => {
      if (type==='posts') postsResults.innerHTML = html;
     if (type==='users') {
  usersResults.innerHTML = html;
  attachUserInteractivity(usersResults);
}

      if (type==='categories') {
        categoriesResults.innerHTML = html;
        attachCategoryActions();
      }
    });
}

// search when typing
searchInput.addEventListener("keyup", () => {
  if (document.getElementById("posts-tab").classList.contains("active")) search('posts');
  else if (document.getElementById("users-tab").classList.contains("active")) search('users');
  else search('categories');
});

// tab switching
document.getElementById("posts-tab").addEventListener("click", () => switchTab("posts"));
document.getElementById("users-tab").addEventListener("click", () => switchTab("users"));
document.getElementById("categories-tab").addEventListener("click", () => switchTab("categories"));

function switchTab(tab) {
  document.querySelectorAll(".tab").forEach(t=>t.classList.remove("active"));
  document.getElementById(tab+"-tab").classList.add("active");

  postsResults.style.display = (tab==="posts") ? "block" : "none";
  usersResults.style.display = (tab==="users") ? "block" : "none";
  categoriesResults.style.display = (tab==="categories") ? "block" : "none";

  search(tab);
}

// Users tab interactivity
function attachUserInteractivity(scope = document) {
  // Click user card -> open that user's profile (but ignore clicks on buttons)
  scope.querySelectorAll(".user-card").forEach(card => {
    const uid = card.dataset.userId;
    if (!uid) return;
    card.addEventListener("click", (e) => {
      // if click target is a button (follow etc.) or inside a button, don't navigate
      if (e.target.closest('button')) return;
      window.location.href = "user_profile_search.php?uid=" + uid;
    });
  });

  // Follow/unfollow buttons (delegated)
  scope.querySelectorAll(".follow-btn").forEach(btn => {
    // ensure we have data-user-id
    const uid = btn.dataset.userId;
    if (!uid) return;

    btn.addEventListener("click", async (e) => {
      e.stopPropagation(); // prevent the card click
      btn.disabled = true;
      const prevText = btn.textContent;

      try {
        // Send JSON; include credentials so session cookie is sent
        const res = await fetch("follow.php", {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          body: JSON.stringify({ user_id: uid })
        });

        // try parse JSON robustly
        let data = null;
        try {
          data = await res.json();
        } catch (err) {
          console.error("follow.php returned non-JSON:", await res.text());
        }

        if (data && data.success) {
          // Update visual state
          btn.textContent = data.following ? "Unfollow" : "Follow";
          btn.setAttribute("aria-pressed", data.following ? "true" : "false");
        } else {
          // fallback: toggle based on previous label if server didn't provide expected response
          btn.textContent = (prevText && prevText.toLowerCase().startsWith("follow")) ? "Unfollow" : "Follow";
        }
      } catch (err) {
        console.error("Follow toggle error:", err);
      } finally {
        btn.disabled = false;
      }
    });
  });
}

// Category "View Posts" action
function attachCategoryActions() {
  categoriesResults.querySelectorAll(".view-category-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const cat = btn.dataset.category;
      fetch("search_backend.php?type=posts&q=" + encodeURIComponent(cat))
        .then(res => res.text())
        .then(html => {
          categoriesResults.innerHTML = html;
        });
    });
  });
}
</script>
</body>
</html>
