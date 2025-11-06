<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];

/* -- Current user info -- */
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;
$currentUserName = htmlspecialchars($currentUser['uname']);
$currentUserImg = !empty($currentUser['profile_img']) ? $currentUser['profile_img'] : "uploads/default_profile.png";

/* --- Helper function for post metadata --- */
function getPostMeta($conn, $post_id, $current_user_id) {
    $post_id = (int)$post_id;
    $likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id"))['c'] ?? 0;
    $comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM comments WHERE post_id=$post_id"))['c'] ?? 0;

    $liked = 0; $bookmarked = 0;
    if ($current_user_id) {
        $liked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id AND user_id=$current_user_id"))['c'] ?? 0;
        $bookmarked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookmarks WHERE post_id=$post_id AND user_id=$current_user_id"))['c'] ?? 0;
    }

    return ['like_count'=>(int)$likes,'comment_count'=>(int)$comments,'liked'=>$liked?1:0,'bookmarked'=>$bookmarked?1:0];
}

/* --- Fetch bookmarked posts --- */
function fetchBookmarkedPosts($conn, $current_user_id, $email) {
    $sql = "
        SELECT p.*, c.uname, c.email, c.profile_img
        FROM bookmarks b
        JOIN posts p ON b.post_id = p.post_id
        JOIN credentials c ON p.user_id = c.user_id
        WHERE b.user_id = $current_user_id
        ORDER BY p.created_at DESC
    ";
    $result = mysqli_query($conn, $sql);
    $html = "";
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $meta = getPostMeta($conn, $row['post_id'], $current_user_id);
            $isOwner = ($row['email'] === $email);

            $followLabel = "Follow";
            if (!$isOwner && $current_user_id) {
                $isFollowing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id={$row['user_id']}"))['c'] ?? 0;
                $followLabel = $isFollowing ? "Unfollow" : "Follow";
            }

            $likeCls = $meta['liked'] ? 'icon liked' : 'icon';
            $bmIconSrc = "assets/bookmark-" . ($meta['bookmarked'] ? "filled" : "outline") . ".svg";
            $thumb = !empty($row['img_url']) ? "<div class='post-thumbnail'><img src='".htmlspecialchars($row['img_url'])."' alt='Thumbnail'></div>" : "";

            $safeTitle = htmlspecialchars($row['title']);
            $safeEmail = htmlspecialchars($row['uname']);
            $safeDate  = htmlspecialchars($row['created_at']);
            $safeExcerpt = nl2br(substr(htmlspecialchars($row['content']), 0, 120));
            $safeCategory = !empty($row['category']) ? htmlspecialchars($row['category']) : "General";
            $profileImg = !empty($row['profile_img']) ? htmlspecialchars($row['profile_img']) : "uploads/default_profile.png";

            $html .= "
            <div class='post-card' data-post-id='{$row['post_id']}' data-user-id='{$row['user_id']}'>
                <div class='post-content'>
                    <div class='post-meta'>
                        <img src='{$profileImg}' alt='Profile' style='width:24px;height:24px;border-radius:50%;margin-right:6px;vertical-align:middle;'>
                        <strong>{$safeEmail}</strong> | {$safeDate}
                    </div>
                    <h3 class='post-title'>{$safeTitle}</h3>
                   <span class='topic-pill'>{$safeCategory}</span>
                    <p class='post-excerpt'>{$safeExcerpt}...</p>
                    <div class='post-actions'>
                        <button class='{$likeCls}' data-action='like'>
                            <span class='ico'>&#10084;</span> <span class='count like-count'>{$meta['like_count']}</span>
                        </button>
                        <a class='icon' href='view.php?pid={$row['post_id']}#comments'>
                            <span class='ico'>&#128172;</span> <span class='count'>{$meta['comment_count']}</span>
                        </a>
                       
                        <a class='read-link' href='view.php?pid={$row['post_id']}'>Read</a>
                    </div>
                </div>
                {$thumb}
                <div class='menu-wrap'>
                    <button class='more-btn' aria-expanded='false' title='More'>⋯</button>
                    <div class='more-menu' role='menu'>
                        <button class='menu-item' data-action='report'>Report post</button>
                        " . (!$isOwner ? "<button class='menu-item' data-action='follow'>{$followLabel}</button>" : "") . "
                        " . ($isOwner ? "<a class='menu-item' href='edit.php?pid={$row['post_id']}'>Edit</a>" : "") . "
                        " . ($isOwner ? "<a class='menu-item danger' href='delete.php?pid={$row['post_id']}' onclick=\"return confirm('Delete this post?');\">Delete</a>" : "") . "
                    </div>
                </div>
            </div>";
        }
    } else $html .= "<p>No bookmarks yet.</p>";
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>NoCap Press - Bookmarks</title>
<link rel="stylesheet" href="home.css">
<style>
/* --- Top header spanning full width above sidebar --- */
.top-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--topbar-height);
  background: #ffffff;
  border-bottom: 1px solid #e9e9e9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  z-index: 1300;
  box-shadow: 0 2px 6px rgba(0,0,0,0.03);
  font-family: Georgia, serif;
  font-weight: 700;
  font-size: 22px;
  color: #111;
}

/* user info on top-right */
.top-header .user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  color: #333;
}

.top-header .user-info img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}

/* --- Sidebar now starts BELOW the header --- */
.side-nav {
  position: fixed;
  top: var(--topbar-height);
  left: 0;
  width: var(--sidebar-width);
  height: calc(100% - var(--topbar-height));
  background: #ffffff;
  border-right: 1px solid #e9e9e9;
  overflow-y: auto;
  z-index: 1100;
}

/* --- Main content with spacing under header --- */
.main-content {
  margin-left: var(--sidebar-width);
  padding: calc(var(--topbar-height) + 20px) 20px 60px;
}
</style>


</head>
<body>
<div class="top-header">
  <div class="site-title">NoCap Press</div>
  <div class="user-info">
    <img src="<?= $currentUserImg ?>" alt="Profile">
    <span><?= $currentUserName ?></span>
  </div>
</div>

<!-- Sidebar -->
<nav class="side-nav">
    <a href="home.php" class="menu-item">Home</a>
    <a href="search.php" class="menu-item">Search</a>
    <a href="create_blog.php" class="menu-item">Create</a>
    <a href="bookmarks.php" class="menu-item active-link">Bookmarks</a>
    <a href="profile.php" class="menu-item">Profile</a>
</nav>

<!-- Main Content -->
<div class="main-content">
    <h2>Your Bookmarks</h2>
    <hr>
    <?php echo fetchBookmarkedPosts($conn, $current_user_id, $email); ?>
</div>

<script>
document.querySelectorAll(".post-card [data-action='like']").forEach(btn=>{
    btn.addEventListener("click", async ()=>{
        const card=btn.closest(".post-card");
        const postId=card.dataset.postId;
        try{
            const res=await fetch("like.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({post_id:postId})});
            const data=await res.json();
            if(data.success){
                card.querySelector(".like-count").textContent=data.like_count;
                btn.classList.toggle("liked",!!data.liked);
            }
        }catch(e){console.error(e);}
    });
});
document.querySelectorAll(".post-card [data-action='bookmark']").forEach(btn=>{
    btn.addEventListener("click", async ()=>{
        const card=btn.closest(".post-card");
        const postId=card.dataset.postId;
        const img=btn.querySelector(".bm-icon");
        try{
            const res=await fetch("bookmark.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({post_id:postId})});
            const data=await res.json();
            if(data.success){
                if(img) img.src=data.bookmarked?"assets/bookmark-filled.svg":"assets/bookmark-outline.svg";
                if(!data.bookmarked) card.remove();
            }
        }catch(e){console.error(e);}
    });
});
document.querySelectorAll(".post-card .more-btn").forEach(btn=>{
    btn.addEventListener("click", e=>{
        e.stopPropagation();
        const menu=btn.nextElementSibling;
        document.querySelectorAll(".more-menu").forEach(m=>{if(m!==menu)m.style.display="none";});
        menu.style.display=menu.style.display==="flex"?"none":"flex";
    });
});
document.addEventListener("click", ()=>document.querySelectorAll(".more-menu").forEach(m=>m.style.display="none"));
</script>

</body>
</html>
