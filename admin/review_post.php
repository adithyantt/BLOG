<?php
// admin/review_post.php
include "../config.php";
session_start();

// Allow only admins
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Validate post id
if (!isset($_GET['pid']) || !ctype_digit($_GET['pid'])) {
    echo "Invalid Post ID.";
    exit();
}
$post_id = (int) $_GET['pid'];

/* ------------ Fetch post + author ------------- */
$post_sql = "
    SELECT p.*, u.uname, u.email
    FROM posts p
    JOIN credentials u ON p.user_id = u.user_id
    WHERE p.post_id = ?
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $post_sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$post_res = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($post_res);

/* ------------ Fetch comments ------------- */
$com_sql = "
    SELECT c.comment_id, c.comment, c.status, c.created_at, u.uname
    FROM comments c
    JOIN credentials u ON c.user_id = u.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at DESC
";
$cst = mysqli_prepare($conn, $com_sql);
mysqli_stmt_bind_param($cst, "i", $post_id);
mysqli_stmt_execute($cst);
$comments = mysqli_stmt_get_result($cst);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Review Post</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
  body { font-family: Arial, sans-serif; padding: 24px; }
  a.back { text-decoration: none; margin-bottom: 16px; display: inline-block; }
  .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
  .muted { color: #6b7280; font-size: 14px; }
  img.post-img { max-width: 420px; height: auto; display: block; margin: 10px 0; border-radius: 8px; }
  .btn { padding: 6px 12px; border: 0; border-radius: 6px; margin-right: 8px; cursor: pointer; }
  .btn-danger { background:#ef4444; color:#fff; }
  .btn-warn { background:#f59e0b; color:#fff; }
  .btn-safe { background:#10b981; color:#fff; }
  .row { display:flex; align-items:center; gap:10px; flex-wrap: wrap; }
  .comment { border-top:1px solid #eee; padding-top:12px; margin-top:12px; }
</style>
</head>
<body>

<a href="manage_posts.php" class="back">← Back to Manage Posts</a>

<?php if (!$post): ?>
  <div class="card"><strong>Post not found.</strong></div>
<?php else: ?>
  <div class="card" id="post-card-<?php echo $post_id; ?>">
    <h2 style="margin:0 0 8px 0;"><?php echo htmlspecialchars($post['title']); ?></h2>
    <div class="muted">Posted on <?php echo htmlspecialchars($post['created_at']); ?></div>

    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

    <?php if (!empty($post['img_url'])): ?>
      <img class="post-img" src="../<?php echo htmlspecialchars($post['img_url']); ?>" alt="Post image">
    <?php endif; ?>

    <p><strong>Author:</strong> <?php echo htmlspecialchars($post['uname']); ?> (<?php echo htmlspecialchars($post['email']); ?>)</p>
    <p><strong>Status:</strong> 
      <span id="post-status-<?php echo $post_id; ?>"><?php echo htmlspecialchars($post['status']); ?></span>
    </p>

    <div class="row">
      <button class="btn toggle-post <?php echo ($post['status'] === 'active') ? 'btn-warn' : 'btn-safe'; ?>"
              data-pid="<?php echo $post_id; ?>">
        <?php echo ($post['status'] === 'active') ? 'Suspend Post' : 'Unsuspend Post'; ?>
      </button>

      <button class="btn btn-danger delete-post" data-pid="<?php echo $post_id; ?>">
        Delete Post
      </button>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">Comments</h3>

    <?php if (mysqli_num_rows($comments) === 0): ?>
      <p class="muted">No comments yet.</p>
    <?php else: ?>
      <?php while ($cm = mysqli_fetch_assoc($comments)): ?>
        <div class="comment" id="comment-<?php echo $cm['comment_id']; ?>">
          <div class="muted"><?php echo htmlspecialchars($cm['created_at']); ?></div>
          <p><strong><?php echo htmlspecialchars($cm['uname']); ?>:</strong>
             <?php echo nl2br(htmlspecialchars($cm['comment'])); ?></p>
          <p><strong>Status:</strong> 
             <span class="comment-status"><?php echo htmlspecialchars($cm['status']); ?></span>
          </p>

          <button class="btn toggle-comment <?php echo ($cm['status'] === 'active') ? 'btn-warn' : 'btn-safe'; ?>"
                  data-cid="<?php echo $cm['comment_id']; ?>">
            <?php echo ($cm['status'] === 'active') ? 'Suspend Comment' : 'Unsuspend Comment'; ?>
          </button>

          <button class="btn btn-danger delete-comment" data-cid="<?php echo $cm['comment_id']; ?>">
            Delete Comment
          </button>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<script>
$(function(){
  // Toggle post
  $(document).on('click', '.toggle-post', function(){
    const btn = $(this);
    const pid = btn.data('pid');
    btn.prop('disabled', true);

    $.ajax({
      url: 'toggle_status.php',
      method: 'POST',
      dataType: 'json',
      data: { type: 'post', id: pid },
      success: function(resp){
        if(resp.success){
          $('#post-status-' + pid).text(resp.new_status);
          if(resp.new_status === 'suspended'){
            btn.removeClass('btn-warn').addClass('btn-safe').text('Unsuspend Post');
          } else {
            btn.removeClass('btn-safe').addClass('btn-warn').text('Suspend Post');
          }
        } else { alert(resp.message); }
      },
      complete: function(){ btn.prop('disabled', false); }
    });
  });

  // Delete post
  $(document).on('click', '.delete-post', function(){
    if(!confirm('Delete this post permanently?')) return;
    const pid = $(this).data('pid');
    $.ajax({
      url: 'delete_item.php',
      method: 'POST',
      dataType: 'json',
      data: { type:'post', id:pid },
      success: function(resp){
        if(resp.success){
          $('#post-card-'+pid).fadeOut(function(){ $(this).remove(); });
        } else { alert(resp.message); }
      }
    });
  });

  // Toggle comment
  $(document).on('click', '.toggle-comment', function(){
    const btn = $(this);
    const cid = btn.data('cid');
    btn.prop('disabled', true);
    $.ajax({
      url: 'toggle_status.php',
      method: 'POST',
      dataType: 'json',
      data: { type: 'comment', id: cid },
      success: function(resp){
        if(resp.success){
          const wrapper = $('#comment-'+cid);
          wrapper.find('.comment-status').text(resp.new_status);
          if(resp.new_status === 'suspended'){
            btn.removeClass('btn-warn').addClass('btn-safe').text('Unsuspend Comment');
          } else {
            btn.removeClass('btn-safe').addClass('btn-warn').text('Suspend Comment');
          }
        } else { alert(resp.message); }
      },
      complete: function(){ btn.prop('disabled', false); }
    });
  });

  // Delete comment
  $(document).on('click', '.delete-comment', function(){
    if(!confirm('Delete this comment permanently?')) return;
    const cid = $(this).data('cid');
    $.ajax({
      url: 'delete_item.php',
      method: 'POST',
      dataType: 'json',
      data: { type:'comment', id:cid },
      success: function(resp){
        if(resp.success){
          $('#comment-'+cid).fadeOut(function(){ $(this).remove(); });
        } else { alert(resp.message); }
      }
    });
  });
});
</script>

</body>
</html>
