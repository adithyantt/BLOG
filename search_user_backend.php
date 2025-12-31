<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) { 
    exit("Login required"); 
}

$email = $_SESSION['email'];
$currentUserRes = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($currentUserRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo "<p class='muted'>Enter search text</p>";
    exit;
}

$q_esc = mysqli_real_escape_string($conn, $q);

// ✅ Only active users, excludes admin
$sql = "
SELECT user_id, uname, profile_img, email 
FROM credentials 
WHERE role != 'admin'
  AND status='active'
  AND (uname LIKE '%$q_esc%' OR email LIKE '%$q_esc%')
ORDER BY created_at DESC
";

$res = mysqli_query($conn, $sql);

if ($res && mysqli_num_rows($res) > 0) {
    while ($u = mysqli_fetch_assoc($res)) {
        $uid = (int)$u['user_id'];

        // Profile image
        $profileImg = !empty($u['profile_img'])
            ? (strpos($u['profile_img'], 'uploads/') === false 
                ? 'uploads/' . htmlspecialchars($u['profile_img']) 
                : htmlspecialchars($u['profile_img']))
            : 'uploads/default_profile.png';

        // Follow/unfollow logic
        $followRes = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS c FROM follows WHERE follower_id=$current_user_id AND following_id=$uid"
        );
        $isFollowing = ($followRes && ($r = mysqli_fetch_assoc($followRes))) ? (int)$r['c'] : 0;
        $followLabel = $isFollowing ? "Unfollow" : "Follow";

        $followBtn = ($u['email'] !== $email)
            ? "<button class='follow-btn' data-user-id='{$uid}' aria-pressed='" . ($isFollowing ? "true" : "false") . "'>$followLabel</button>"
            : "";

        // Output user card
        echo "
        <div class='user-card' data-user-id='{$uid}'>
            <img src='" . htmlspecialchars($profileImg) . "' alt='Profile'>
            <div class='user-info'>
                <strong>" . htmlspecialchars($u['uname']) . "</strong>
                <span class='view-profile'>View Profile</span>
            </div>
            {$followBtn}
        </div>";
    }
} else {
    echo "<p class='muted'>No users found</p>";
}
?>
