<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$current_email = $_SESSION['email'];
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

// Fetch current user info
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $current_email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$currentUserName = $currentUser['uname'];
$currentUserImg = !empty($currentUser['profile_img']) ? $currentUser['profile_img'] : "uploads/default_profile.png";

// Get UID from URL
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
if ($uid <= 0) die("<h2 style='text-align:center;margin-top:50px;'>Invalid user.</h2>");

// Fetch profile user info
$sqlUser = "SELECT user_id, uname, bio, profile_img, status FROM credentials WHERE user_id=$uid LIMIT 1";
$resUser = mysqli_query($conn, $sqlUser);

if (!$resUser || mysqli_num_rows($resUser) === 0) {
    die("<h2 style='text-align:center;margin-top:50px;'>User not found.</h2>");
}

$user = mysqli_fetch_assoc($resUser);
$uname = htmlspecialchars($user['uname']);
$bio = !empty($user['bio']) ? htmlspecialchars($user['bio']) : "No bio added yet.";
$profile_img = !empty($user['profile_img']) ? htmlspecialchars($user['profile_img']) : "uploads/default_profile.png";

// Check follow status
$followRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id=$uid");
$isFollowing = ($followRes && ($r = mysqli_fetch_assoc($followRes))) ? (int)$r['c'] : 0;
$followLabel = $isFollowing ? "Unfollow" : "Follow";

// Fetch user's posts (active only)
$sqlPosts = "SELECT p.*, c.uname, c.profile_img, c.email
             FROM posts p
             JOIN credentials c ON p.user_id = c.user_id
             WHERE p.user_id=$uid AND p.status='active'
             ORDER BY p.created_at DESC";
$resPosts = mysqli_query($conn, $sqlPosts);

function renderPosts($conn, $posts, $current_user_id, $current_email) {
    if (!$posts || mysqli_num_rows($posts) === 0) return "<p>No posts found.</p>";
    $html = "";
    while ($post = mysqli_fetch_assoc($posts)) {
        $title = htmlspecialchars($post['title']);
        $content = nl2br(htmlspecialchars(substr($post['content'], 0, 120)));
        $date = htmlspecialchars($post['created_at']);
        $category = !empty($post['category']) ? htmlspecialchars($post['category']) : "General";
        $profileImg = !empty($post['profile_img']) ? htmlspecialchars($post['profile_img']) : "uploads/default_profile.png";

        $likes = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id={$post['post_id']}"))['c'] ?? 0);
        $comments = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM comments WHERE post_id={$post['post_id']}"))['c'] ?? 0);
        $liked = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id={$post['post_id']} AND user_id=$current_user_id"))['c'] ?? 0);
        $bookmarked = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookmarks WHERE post_id={$post['post_id']} AND user_id=$current_user_id"))['c'] ?? 0);
        $likeCls = $liked ? "icon liked" : "icon";
        $bmIconSrc = $bookmarked ? "assets/bookmark-filled.svg" : "assets/bookmark-outline.svg";
        $isOwner = ($post['email'] === $current_email);
        $isFollowing = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id={$post['user_id']}"))['c'] ?? 0);
        $followLabel = $isFollowing ? "Unfollow" : "Follow";
        $views = (int)($post['views'] ?? 0);
        $thumb = !empty($post['img_url']) ? "<div class='post-thumbnail'><img src='".htmlspecialchars($post['img_url'])."' alt='Thumbnail'></div>" : "";

        $html .= "
        <div class='post-card' data-post-id='{$post['post_id']}' data-user-id='{$post['user_id']}'>
            <div class='post-content'>
                <div class='post-meta'>
                    <img src='{$profileImg}' alt='Profile'>
                    <strong>".htmlspecialchars($post['uname'])."</strong> | {$date}
                </div>
                <h3 class='post-title'>{$title}</h3>
                <span class='topic-pill'>{$category}</span>
                <p class='post-excerpt'>{$content}...</p>
                <div class='post-actions'>
                    <button class='{$likeCls}' data-action='like'><span class='ico'>&#10084;</span> <span class='count like-count'>{$likes}</span></button>
                    <a class='icon' href='view.php?pid={$post['post_id']}#comments'><span class='ico'>&#128172;</span> <span class='count'>{$comments}</span></a>
                    <span class='post-views'>👁️ {$views}</span>
                    <a class='read-link' href='view.php?pid={$post['post_id']}'>Read</a>
                </div>
            </div>
            {$thumb}
            <div class='menu-wrap'>
                <button class='more-btn' aria-expanded='false' title='More'>⋯</button>
                <div class='more-menu' role='menu'>
                    ".(!$isOwner ? "<button class='menu-item' data-action='follow'>{$followLabel}</button>" : "")."
                    ".($isOwner ? "<a class='menu-item' href='edit.php?pid={$post['post_id']}'>Edit</a>" : "")."
                    ".($isOwner ? "<a class='menu-item danger' href='delete.php?pid={$post['post_id']}' onclick=\"return confirm('Delete this post?');\">Delete</a>" : "")."
                    <button class='menu-item' data-action='report'>Report post</button>
                </div>
            </div>
        </div>";
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $uname ?> | NoCap Press</title>
<link rel="stylesheet" href="home.css">
</head>
<body>

<div class="brand-title">NoCapPress</div>
<nav class="side-nav">
  <a href="home.php" class="menu-item">Home</a>
  <a href="search.php" class="menu-item active-link">Search</a>
  <a href="create_blog.php" class="menu-item">Create</a>
  <a href="bookmarks.php" class="menu-item">Bookmark</a>
  <a href="profile.php" class="menu-item">Profile</a>
</nav>

<div class="top-header">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="<?= $currentUserImg ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;">
        <span><?= htmlspecialchars($currentUserName) ?></span>
    </div>
</div>

<div class="main-content">
    <div class="profile-header" style="display:flex;align-items:center;gap:20px;margin-bottom:24px;">
        <img src="<?= $profile_img ?>" alt="Profile" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
        <div class="info">
            <h2><?= $uname ?></h2>
            <p><?= $bio ?></p>
            <?php if ($current_user_id !== $uid): ?>
                <button id="followBtn" class="follow-btn" data-user-id="<?= $uid ?>"><?= $followLabel ?></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="posts-list">
        <h3 style="margin-bottom:12px;">Posts by <?= $uname ?></h3>
        <?= renderPosts($conn, $resPosts, $current_user_id, $current_email) ?>
    </div>
</div>

<script>
const followBtn = document.getElementById('followBtn');
if(followBtn){
    followBtn.addEventListener('click', async () => {
        const uid = followBtn.dataset.userId;
        followBtn.disabled = true;
        const prevText = followBtn.textContent;
        try {
            const res = await fetch('follow.php', {
                method:'POST',
                credentials:'include',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({user_id: uid})
            });
            const data = await res.json();
            if(data.success){
                followBtn.textContent = data.following ? "Unfollow" : "Follow";
            } else {
                followBtn.textContent = prevText;
            }
        } catch(err){
            console.error(err);
            followBtn.textContent = prevText;
        } finally {
            followBtn.disabled = false;
        }
    });
}
</script>

</body>
</html>
