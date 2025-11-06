<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    echo "<p>Please log in to search.</p>";
    exit();
}

$email = $_SESSION['email'];

/* ----------------------------
   Current user info
---------------------------- */
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;

/* ----------------------------
   Helper functions
---------------------------- */
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
            $meta = getPostMeta($conn, $row['post_id'], $current_user_id);
           $isOwner = ($row['user_id'] == $current_user_id);


            $uid = (int)$row['user_id'];
            $isFollowing = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id=$uid"
            ))['c'] ?? 0;

            $followLabel = $isFollowing ? "Unfollow" : "Follow";
            $likeCls = $meta['liked'] ? 'icon liked' : 'icon';
           

            $thumb = "";
            if (!empty($row['img_url'])) {
                $thumb = "<div class='post-thumbnail'><img src='" . htmlspecialchars($row['img_url']) . "' alt='Thumbnail'></div>";
            }

            $safeTitle = htmlspecialchars($row['title']);
            $safeName = htmlspecialchars($row['uname']);
            $safeDate  = htmlspecialchars($row['created_at']);
            $safeExcerpt = nl2br(substr(htmlspecialchars($row['content']), 0, 120));
            $safeCategory = !empty($row['category']) ? htmlspecialchars($row['category']) : "General";

            $profileImg = (!empty($row['profile_img']) && file_exists($row['profile_img'])) 
                ? htmlspecialchars($row['profile_img']) 
                : "uploads/default_profile.png";

            // ✅ Profile link fixed — goes to user profile, not self
            $profileLink = "user_profile.php?uid={$uid}";

            $html .= "
            <div class='post-card' data-post-id='{$row['post_id']}' data-user-id='{$uid}'>
                <div class='post-content'>
                    <div class='post-meta'>
                        <a href='{$profileLink}' class='user-link'>
                            <img src='{$profileImg}' alt='Profile' style='width:24px;height:24px;border-radius:50%;margin-right:6px;vertical-align:middle;'>
                            <strong>{$safeName}</strong>

                        </a> | {$safeDate}
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
                        " . (!$isOwner ? "<button class='menu-item follow-toggle' data-user-id='{$uid}' data-following='{$isFollowing}'>{$followLabel}</button>" : "") . "
                        " . ($isOwner ? "<a class='menu-item' href='edit.php?pid={$row['post_id']}'>Edit</a>" : "") . "
                        " . ($isOwner ? "<a class='menu-item danger' href='delete.php?pid={$row['post_id']}' onclick=\"return confirm('Delete this post?');\">Delete</a>" : "") . "
                    </div>
                </div>
            </div>";
        }
    } else {
        $html .= "<p>No results found.</p>";
    }

    return $html;
}

/* ----------------------------
   Actual Search Logic
---------------------------- */
$query = trim($_GET['q'] ?? '');
$type  = $_GET['type'] ?? 'posts';

if ($query === '') {
    echo "<p>Please enter a search term.</p>";
    exit();
}

$escaped = mysqli_real_escape_string($conn, $query);

if ($type === 'categories') {
    $sql_cat = "SELECT DISTINCT category FROM posts WHERE category LIKE '%$escaped%' ORDER BY category ASC";
    $res = mysqli_query($conn, $sql_cat);
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $cat = htmlspecialchars($row['category']);
            echo "<div class='category-item'>
                    <span>{$cat}</span>
                    <button class='view-category-btn' data-category='{$cat}'>View Posts</button>
                  </div>";
        }
    } else {
        echo "<p>No categories found.</p>";
    }
    exit();
}

/* ✅ FIXED SQL QUERY: Excludes suspended users/posts and ensures all joins valid */
$sql_search = "
    SELECT p.*, c.uname, c.profile_img, c.user_id
    FROM posts p
    INNER JOIN credentials c ON p.user_id = c.user_id
    WHERE p.status != 'suspended'
      AND c.status != 'suspended'
      AND (
          p.title LIKE '%$escaped%' 
          OR p.content LIKE '%$escaped%' 
          OR c.uname LIKE '%$escaped%' 
          OR p.category LIKE '%$escaped%'
      )
    ORDER BY p.created_at DESC
";


echo fetchPostsForFeed($conn, $sql_search, $current_user_id, $email);
?>
