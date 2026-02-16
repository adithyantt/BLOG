
<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Validate post id
if (!isset($_GET['pid']) || !is_numeric($_GET['pid'])) {
    echo "Invalid Post ID.";
    exit();
}

$post_id = (int) $_GET['pid'];

// Current users
$current_user_email = $_SESSION['email'];
$user_res = mysqli_query($conn, "SELECT user_id, uname FROM credentials WHERE email='".mysqli_real_escape_string($conn,$current_user_email)."'");
$user_data = mysqli_fetch_assoc($user_res);
$current_user_id = $user_data['user_id'];
$current_user_name = $user_data['uname'];

// Fetch post with author info
$query = "SELECT p.*, c.uname AS author, c.user_id AS author_id 
          FROM posts p 
          JOIN credentials c ON p.user_id = c.user_id 
          WHERE p.post_id = $post_id";
$result = mysqli_query($conn, $query);
// Increment views
mysqli_query($conn, "UPDATE posts SET views = views + 1 WHERE post_id = $post_id");

if (!$row = mysqli_fetch_assoc($result)) {
    echo "Post not found.";
    exit();
}

// Updated view count
$views_count = (int) ($row['views'] ?? 0) + 1; // because you incremented above


$title = $row['title'];
$content = $row['content'];
$img_url = $row['img_url'];
$created_at = $row['created_at'];
$author = $row['author'];
$author_id = $row['author_id'];

// Fetch initial states
$likes_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM likes WHERE post_id=$post_id"));
$user_liked = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM likes WHERE post_id=$post_id AND user_id=$current_user_id")) > 0;
$user_bookmarked = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM bookmarks WHERE post_id=$post_id AND user_id=$current_user_id")) > 0;
$user_following = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM follows WHERE follower_id=$current_user_id AND following_id=$author_id")) > 0;

$comments_res = mysqli_query($conn, "SELECT c.comment_id, c.comment, c.created_at, c.user_id, u.uname 
                                     FROM comments c 
                                     JOIN credentials u ON c.user_id=u.user_id 
                                     WHERE c.post_id=$post_id 
                                     ORDER BY c.created_at DESC");

                                     


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($title); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Georgia, serif; margin: 40px; background: #fff; color: #222; }
.post-container { max-width: 800px; margin: auto; }
.post-title { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
.post-meta { color: #555; font-size: 15px; margin-bottom: 25px; }
.post-image { max-width: 100%; margin: 20px 0; border-radius: 8px; }
.post-content { font-size: 20px; line-height: 1.7; white-space: pre-line; margin-bottom: 40px; }

.actions { display: flex; gap: 20px; margin: 20px 0; font-size: 16px; flex-wrap: wrap; }
.action-btn { border: none; background: none; font-size: 16px; cursor: pointer; color: #555; }
.action-btn:hover { color: #000; }

.comments { margin-top: 40px; }
.comment-form textarea { width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 5px; outline:none; resize: vertical; }
.comment-form textarea:focus { border-color:#222; }
.comment { border-top: 1px solid #eee; padding: 12px 0; position: relative; }
.comment b { color: #333; }

/* More-menu styling */
.more-wrap { display:inline-block; position:relative; }
.more-menu { display:none; position:absolute; right:0; background:#fff; border:1px solid #ccc; z-index:10; min-width:100px; }
.more-menu .menu-item { display:block; padding:5px 10px; cursor:pointer; border:none; background:none; text-align:left; width:100%; }
.more-menu .menu-item:hover { background:#f0f0f0; }
.more-menu .danger { color:red; }

/* Report modal styling */
#report-modal, #report-comment-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100; }
.modal-box { background:#fff;padding:20px;border-radius:8px;max-width:400px;margin:100px auto; position:relative; }
</style>
</head>
<body>
<div class="post-container">

<a href="home.php" style="display:inline-block; margin-bottom:15px; text-decoration:none;">
    <button style="padding:6px 12px; border:none; background:#222; color:#fff; cursor:pointer;">⬅ Back to Home</button>
</a>

<div class="post-title"><?php echo htmlspecialchars($title); ?></div>
<div class="post-meta">

    By <b><?php echo htmlspecialchars($author); ?></b> · <?php echo $created_at; ?> · <span><?php echo $views_count; ?> views</span>


    <?php if ($current_user_id != $author_id): ?>
        <button id="follow-btn" class="action-btn"><?php echo $user_following ? "Unfollow" : "Follow"; ?></button>
    <?php endif; ?>
</div>

<div class="actions">
    <button id="like-btn" class="action-btn">
        <i class="fa fa-heart"></i> <?php echo $user_liked ? "Unlike" : "Like"; ?> (<?php echo $likes_count; ?>)
    </button>

    <a href="#comments" class="action-btn"><i class="fa fa-comment"></i> Comment</a>

    <button id="bookmark-btn" class="action-btn">
        <i class="fa fa-bookmark"></i> <?php echo $user_bookmarked ? "Unbookmark" : "Bookmark"; ?>
    </button>

    <button id="read-btn" class="action-btn"><i class="fa fa-volume-up"></i> Read Aloud</button>

    <div class="more-wrap">
        <button class="more-btn action-btn">⋯</button>
        <div class="more-menu">
            <button class="menu-item" data-action="report">Report Post</button>
            <?php if($current_user_id != $author_id): ?>
                <button class="menu-item" data-action="follow"><?php echo $user_following ? "Unfollow" : "Follow"; ?></button>
            <?php endif; ?>
            <?php if($current_user_id == $author_id): ?>
                <a class="menu-item" href="edit.php?pid=<?php echo $post_id; ?>">Edit</a>
                <a class="menu-item danger" href="delete.php?pid=<?php echo $post_id; ?>" onclick="return confirm('Delete this post?');">Delete</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($img_url)): ?>
<img src="<?php echo htmlspecialchars($img_url); ?>" alt="Blog Image" class="post-image">
<?php endif; ?>

<div class="post-content" id="post-content"><?php echo nl2br(htmlspecialchars($content)); ?></div>

<!-- COMMENTS -->
<div class="comments" id="comments">
<h3>Comments</h3>
<form id="comment-form" class="comment-form">
    <textarea name="comment" rows="3" placeholder="Write a comment..." required></textarea><br>
    <button type="submit">Post Comment</button>
</form>
<div id="comments-list">
<?php while ($c = mysqli_fetch_assoc($comments_res)): ?>
    <div class="comment" 
         id="comment-<?php echo $c['comment_id']; ?>" 
         data-comment-id="<?php echo $c['comment_id']; ?>" 
         data-user-id="<?php echo $c['user_id']; ?>" 
         data-post-author-id="<?php echo $author_id; ?>">
        <b><?php echo htmlspecialchars($c['uname']); ?></b> (<?php echo $c['created_at']; ?>)<br>
        <?php echo nl2br(htmlspecialchars($c['comment'])); ?>

        <div class="more-wrap" style="float:right;">
            <button class="more-btn action-btn">⋯</button>
            <div class="more-menu">
                <?php if($current_user_id == $c['user_id'] || $current_user_id == $author_id): ?>
                    <button class="menu-item delete-comment">Delete</button>
                <?php endif; ?>
                <button class="menu-item report-comment">Report</button>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
</div>

<!-- Report Modals -->
<div id="report-modal">
  <div class="modal-box">
    <h3 id="report-title">Report Post</h3>
    <label>Reason</label>
    <select id="report-reason" style="width:100%;margin:5px 0;">
      <option value="">Select reason</option>
      <option value="spam">Spam</option>
      <option value="abuse">Abusive or harmful content</option>
      <option value="misinfo">Misinformation</option>
      <option value="plagiarism">Plagiarism</option>
      <option value="other">Other</option>
    </select>
    <label style="margin-top:8px;display:block;">Additional details (optional)</label>
    <textarea id="report-text" placeholder="Describe the issue..." style="width:100%;height:80px;margin:5px 0;"></textarea>
    <div class="actions" style="text-align:right;">
      <button id="report-cancel" style="margin-right:5px;">Cancel</button>
      <button id="report-submit" class="primary">Submit</button>
    </div>
  </div>
</div>

<div id="report-comment-modal">
  <div class="modal-box">
    <h3>Report Comment</h3>
    <label>Reason</label>
    <select id="report-comment-reason" style="width:100%; margin:5px 0;">
      <option value="">Select reason</option>
      <option value="spam">Spam</option>
      <option value="abuse">Abusive or harmful content</option>
      <option value="misinfo">Misinformation</option>
      <option value="plagiarism">Plagiarism</option>
      <option value="other">Other</option>
    </select>
    <textarea id="report-comment-text" placeholder="Describe the issue..." style="width:100%; height:80px; margin:5px 0;"></textarea>
    <div style="text-align:right;">
      <button id="report-comment-cancel">Cancel</button>
      <button id="report-comment-submit">Submit</button>
    </div>
  </div>
</div>

<script>
const currentUserId = <?php echo $current_user_id; ?>;
let currentReportCommentId = null;

// --- Read Aloud ---
let synth = window.speechSynthesis;
let utterance;
let isSpeaking = false;

document.getElementById("read-btn").addEventListener("click", () => {
    if (isSpeaking) {
        synth.cancel();
        isSpeaking = false;
        document.getElementById("read-btn").innerHTML = `<i class="fa fa-volume-up"></i> Read Aloud`;
    } else {
        let title = document.querySelector(".post-title").innerText;
        let content = document.getElementById("post-content").innerText; 
        let text = title + ". " + content;  // 👈 now includes title
        
        utterance = new SpeechSynthesisUtterance(text);
        synth.speak(utterance);
        isSpeaking = true;
        document.getElementById("read-btn").innerHTML = `⏹ Stop Reading`;
        utterance.onend = () => {
            isSpeaking = false;
            document.getElementById("read-btn").innerHTML = `<i class="fa fa-volume-up"></i> Read Aloud`;
        };
    }
});
// --- Like ---
document.getElementById('like-btn').addEventListener('click', async function(){
    const res = await fetch('like.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({post_id: <?php echo $post_id; ?>})
    });
    const data = await res.json();
    if(data.success){
        this.innerHTML = `<i class="fa fa-heart"></i> ${data.liked ? 'Unlike' : 'Like'} (${data.like_count})`;
    }
});

// --- Bookmark ---
document.getElementById('bookmark-btn').addEventListener('click', async function(){
    const res = await fetch('bookmark.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({post_id: <?php echo $post_id; ?>})
    });
    const data = await res.json();
    if(data.success){
        this.innerHTML = `<i class="fa fa-bookmark"></i> ${data.bookmarked ? 'Unbookmark' : 'Bookmark'}`;
    }
});

// --- Follow ---
<?php if ($current_user_id != $author_id): ?>
document.getElementById('follow-btn').addEventListener('click', async function(){
    const res = await fetch('follow.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({user_id: <?php echo $author_id; ?>})
    });
    const data = await res.json();
    if(data.success) this.innerText = data.following ? 'Unfollow' : 'Follow';
});
<?php endif; ?>

// --- More Menus ---
document.querySelectorAll(".more-btn").forEach(btn=>{
    btn.addEventListener("click", e=>{
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        document.querySelectorAll(".more-menu").forEach(m=>{if(m!==menu) m.style.display='none';});
        menu.style.display = (menu.style.display==='block')?'none':'block';
    });
});
document.addEventListener("click",()=>document.querySelectorAll(".more-menu").forEach(m=>m.style.display='none'));

// --- Comment Events ---
function attachCommentEvents(commentDiv){
    const deleteBtn = commentDiv.querySelector(".delete-comment");
    const reportBtn = commentDiv.querySelector(".report-comment");

    if(deleteBtn){
        deleteBtn.addEventListener("click", async ()=>{ 
            const commentId = commentDiv.dataset.commentId;
            const commentUserId = commentDiv.dataset.userId;
            const postAuthorId = commentDiv.dataset.postAuthorId;
            if(currentUserId != commentUserId && currentUserId != postAuthorId){
                alert("❌ You cannot delete this comment");
                return;
            }
            if(!confirm("Delete this comment?")) return;
            const res = await fetch("delete_comment.php", {
                method: "POST",
                headers: {"Content-Type":"application/json"},
                body: JSON.stringify({comment_id: commentId})
            });
            const data = await res.json();
            if(data.success) commentDiv.remove();
            else alert("❌ " + (data.msg || "Failed to delete comment"));
        });
    }

    if(reportBtn){
        reportBtn.addEventListener("click", ()=>{ 
            currentReportCommentId = commentDiv.dataset.commentId;
            document.getElementById("report-comment-modal").style.display = "block";
        });
    }
}
document.querySelectorAll(".comment").forEach(attachCommentEvents);

// --- New Comment Submit ---
document.getElementById("comment-form").addEventListener("submit", async function(e){
    e.preventDefault();
    const comment = this.comment.value.trim();
    if(!comment) return;

    const res = await fetch("comment.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({post_id: <?php echo $post_id; ?>, comment})
    });
    const data = await res.json();
    if(data.success){
        const div = document.createElement('div');
        div.className = 'comment';
        div.setAttribute('id', `comment-${data.comment_id}`);
        div.setAttribute('data-comment-id', data.comment_id);
        div.setAttribute('data-user-id', data.user_id);
        div.setAttribute('data-post-author-id', <?php echo $author_id; ?>);

        div.innerHTML = `
            <b>${data.uname}</b> (${data.created_at})<br>
            ${data.comment}
            <div class="more-wrap" style="float:right;">
                <button class="more-btn action-btn">⋯</button>
                <div class="more-menu">
                    <button class="menu-item delete-comment">Delete</button>
                    <button class="menu-item report-comment">Report</button>
                </div>
            </div>
        `;
        document.getElementById('comments-list').prepend(div);
        attachCommentEvents(div);
        this.reset();
    } else alert(data.msg || "Failed to post comment");
});

// --- Report Post ---
const reportModal = document.getElementById("report-modal");
document.querySelector(".menu-item[data-action='report']").addEventListener("click", ()=> reportModal.style.display="block");
document.getElementById("report-cancel").addEventListener("click", ()=> reportModal.style.display="none");
document.getElementById("report-submit").addEventListener("click", async ()=>{ 
    const reason = document.getElementById("report-reason").value;
    const details = document.getElementById("report-text").value;
    if(!reason){ alert("Select a reason"); return; }
    const res = await fetch("report_post.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({post_id: <?php echo $post_id; ?>, reason, details})
    });
    const data = await res.json();
    if(data.success){ alert("✅ Report submitted"); reportModal.style.display="none"; }
    else alert("❌ " + (data.msg || "Failed to submit report"));
});


// Report comment
document.getElementById("report-comment-cancel").addEventListener("click", ()=>{
    document.getElementById("report-comment-modal").style.display="none";
    currentReportCommentId = null;
});
document.getElementById("report-comment-submit").addEventListener("click", async ()=>{
    const reason = document.getElementById("report-comment-reason").value;
    const details = document.getElementById("report-comment-text").value;
    if(!reason){ alert("Select a reason"); return; }
    const res = await fetch("report_comment.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ comment_id: currentReportCommentId, reason, details })
    });
    const data = await res.json();
    if(data.success){ alert("✅ Report submitted"); document.getElementById("report-comment-modal").style.display="none"; currentReportCommentId=null; }
    else alert("❌ " + (data.msg || "Failed to report comment"));
});
</script>
</body>
</html>
