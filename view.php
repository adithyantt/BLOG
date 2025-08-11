<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['pid']) || !filter_var($_GET['pid'], FILTER_VALIDATE_INT)) {
    echo "Invalid post ID.";
    exit();
}

$pid = (int)$_GET['pid'];

// Fetch post + author info
$query = "SELECT p.*, c.email, c.user_id AS author_id 
          FROM posts p 
          JOIN credentials c ON p.user_id = c.user_id 
          WHERE p.post_id = $pid";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Post not found.";
    exit();
}
$row = mysqli_fetch_assoc($result);

// Get current user
$current_user_email = $_SESSION['email'];
$current_user_query = "SELECT user_id FROM credentials WHERE email = '$current_user_email'";
$current_user_result = mysqli_query($conn, $current_user_query);
$current_user_data = mysqli_fetch_assoc($current_user_result);
$current_user_id = $current_user_data['user_id'];

// Check if bookmarked
$bookmark_check = mysqli_query($conn, "SELECT 1 FROM bookmarks WHERE user_id=$current_user_id AND post_id=$pid");
$is_bookmarked = mysqli_num_rows($bookmark_check) > 0;

// Check if following
$follow_check = mysqli_query($conn, "SELECT 1 FROM follows WHERE follower_id=$current_user_id AND following_id={$row['author_id']}");
$is_following = mysqli_num_rows($follow_check) > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Blog</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        h2 { margin-bottom: 5px; color: #333; }
        small { color: #777; }
        p { background: #fff; padding: 15px; border-radius: 5px; line-height: 1.6; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .top-actions { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .top-actions button, .top-actions a { background: none; border: none; cursor: pointer; padding: 0; }
        .follow-btn { padding: 5px 10px; border-radius: 5px; background: #007BFF; color: white; cursor: pointer; border: none; }
        .follow-btn.unfollow { background: #6c757d; }
    </style>
    <script>
    // Predefined SVGs (clean, no PHP escaping needed)
    const bookmarkFilled = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>`;
    const bookmarkOutline = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>`;

    function toggleBookmark(pid) {
        fetch('toggle_bookmark.php?pid=' + pid)
            .then(res => res.json())
            .then(data => {
                let icon = document.getElementById('bookmark-icon');
                if (data.status === 'bookmarked') {
                    icon.innerHTML = bookmarkFilled;
                } else {
                    icon.innerHTML = bookmarkOutline;
                }
            })
            .catch(err => console.error('Bookmark toggle error:', err));
    }

    function toggleFollow(author_id) {
        fetch('toggle_follow.php?author_id=' + author_id)
            .then(res => res.json())
            .then(data => {
                let btn = document.getElementById('follow-btn');
                btn.textContent = data.status === 'following' ? 'Unfollow' : 'Follow';
                btn.classList.toggle('unfollow', data.status === 'following');
            })
            .catch(err => console.error('Follow toggle error:', err));
    }

    function sharePost() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert("Post link copied to clipboard!");
        }).catch(err => console.error("Share error:", err));
    }
</script>

</head>
<body>

<h2><?php echo htmlspecialchars($row['title']); ?></h2>
<small>Author: <?php echo htmlspecialchars($row['email']); ?></small>

<div class="top-actions">
    <!-- Bookmark -->
    <button onclick="toggleBookmark(<?php echo $pid; ?>)" title="Bookmark">
        <span id="bookmark-icon">
            <?php echo $is_bookmarked ? bookmarkFilledSVG() : bookmarkOutlineSVG(); ?>
        </span>
    </button>

    <!-- Comment -->
    <a href="comments.php?pid=<?php echo $pid; ?>" title="View Comments">
        <?php echo commentSVG(); ?>
    </a>

    <!-- Share -->
    <button onclick="sharePost()" title="Share Post">
        <?php echo shareSVG(); ?>
    </button>

    <!-- Follow -->
    <?php if ($current_user_id != $row['author_id']): ?>
        <button id="follow-btn" class="follow-btn <?php echo $is_following ? 'unfollow' : ''; ?>" 
                onclick="toggleFollow(<?php echo $row['author_id']; ?>)">
            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
        </button>
    <?php endif; ?>
</div>

<p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>

<div class="actions">
<?php
if ($_SESSION['email'] === $row['email']) {
    echo "<a href='edit.php?pid=$pid'>Edit</a> ";
    echo "<a href='delete.php?pid=$pid' onclick=\"return confirm('Delete this post?');\">Delete</a>";
}
?>
    <br><br>
    <a href='home.php'>Back to Home</a>
</div>

</body>
</html>

<?php
// ICON FUNCTIONS
function bookmarkOutlineSVG() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>';
}
function bookmarkFilledSVG() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>';
}
function commentSVG() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
}
function shareSVG() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';
}
?>
