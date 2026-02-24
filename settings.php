<?php
session_start();
include "config.php";

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$notice = "";
$error = "";

// --- Handle POST actions---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Logout
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: login.html");
        exit();
    }

    // Deactivate or Delete
    if (isset($_POST['account_action']) && in_array($_POST['account_action'], ['deactivate', 'delete'])) {
        $password = trim($_POST['password']);

        // Verify password before destructive actions
        $stmt = mysqli_prepare($conn, "SELECT pwd FROM credentials WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_pass = mysqli_fetch_assoc($result);

        if ($user_pass && password_verify($password, $user_pass['pwd'])) {

            if ($_POST['account_action'] === 'deactivate') {
                // ✅ Change status to 'pending'
                $stmt2 = mysqli_prepare($conn, "UPDATE credentials SET status='pending' WHERE user_id=?");
                mysqli_stmt_bind_param($stmt2, "i", $user_id);
                if (mysqli_stmt_execute($stmt2)) {
                    session_unset();
                    session_destroy();
                    header("Location: login.html?notice=deactivated");
                    exit();
                } else {
                    $error = "Could not deactivate account. Try again.";
                }

            } elseif ($_POST['account_action'] === 'delete') {
                // 🔥 Delete account permanently
                mysqli_begin_transaction($conn);
                try {
                    // Delete user posts
                    $del_posts = mysqli_prepare($conn, "DELETE FROM posts WHERE user_id=?");
                    mysqli_stmt_bind_param($del_posts, "i", $user_id);
                    mysqli_stmt_execute($del_posts);

                    // Delete user record
                    $del_user = mysqli_prepare($conn, "DELETE FROM credentials WHERE user_id=?");
                    mysqli_stmt_bind_param($del_user, "i", $user_id);
                    mysqli_stmt_execute($del_user);

                    mysqli_commit($conn);
                    session_unset();
                    session_destroy();
                    header("Location: login.html?msg=account_deleted");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error deleting account. Please contact support.";
                }
            }
        } else {
            $error = "Incorrect password. Action not performed.";
        }
    }
}

// --- Fetch user info ---
$stmt = mysqli_prepare($conn, "SELECT user_id, uname, email, phone, profile_img, bio, role, status FROM credentials WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}

// Prepare safe display values
$display_name = htmlspecialchars($user['uname']);
$display_email = htmlspecialchars($user['email']);
$display_phone = htmlspecialchars($user['phone'] ?? '');
$display_role = htmlspecialchars($user['role']);
$display_status = htmlspecialchars($user['status']);
$display_bio = htmlspecialchars($user['bio']);
$profile_img = $user['profile_img'] ?? '';
$avatar_url = (!empty($profile_img) && file_exists($profile_img)) ? $profile_img : 'assets/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings - NoCapPress</title>
<link rel="stylesheet" href="login.css">
<style>
  body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:30px; color:#222; }
  .wrap { max-width:1000px; margin:0 auto; }
  .profile-head { display:flex; gap:20px; align-items:center; background:#fff; padding:18px; border-radius:10px; box-shadow:0 6px 16px rgba(0,0,0,.06); }
  .avatar { width:96px; height:96px; border-radius:50%; object-fit:cover; border:2px solid #e6e9ee; }
  .summary { flex:1; }
  .summary h2 { margin:0 0 6px 0; font-size:20px; }
  .summary p { margin:4px 0; color:#556; font-size:14px; }
  .status { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; margin-top:6px; }
  .status.active { background:#e6ffed; color:#1a7f3a; border:1px solid #c7f0d0; }
  .status.pending { background:#fff7e6; color:#9a6a00; border:1px solid #f0ddb8; }
  .status.suspended { background:#ffecef; color:#9a1a3a; border:1px solid #f0cdd4; }
  .grid { margin-top:22px; display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:18px; }
  .card { background:#fff; padding:18px; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.04); text-align:center; }
  .card h3 { margin:10px 0; font-size:16px; }
  .card p { margin:0 0 12px 0; color:#666; font-size:14px; }
  .btn { display:inline-block; padding:9px 16px; background:#000; color:white; border-radius:7px; text-decoration:none; font-weight:600; border:none; cursor:pointer; }
  .btn.secondary { background:#6c757d; }
  .danger { background:#dc3545; }
  .small { font-size:13px; color:#666; margin-top:8px; display:block; }
  .actions { margin-left:auto; display:flex; gap:10px; align-items:center; }
  .bio-box { margin-top:8px; color:#444; font-size:14px; background:#fbfbfb; padding:12px; border-radius:8px; border:1px solid #eee; white-space:pre-wrap; }
  form.inline { display:inline-block; }

  /* password field + toggle styles */
  .pw-wrapper { position: relative; display: block; width: 100%; }
  input[type="password"], input[type="text"] {
    width: 100%;
    padding: 8px 44px 8px 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 6px;
    box-sizing: border-box;
  }
  .pw-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 13px;
    color: #007bff;
    background: transparent;
    border: none;
    padding: 4px 6px;
    line-height: 1;
    z-index: 3;
    user-select: none;
  }
  .pw-toggle:focus {
    outline: none;
    text-decoration: underline;
  }
  @media (max-width: 480px) {
    input[type="password"], input[type="text"] { padding-right: 48px; }
    .pw-toggle { right: 8px; font-size: 12px; }
  }
</style>

<script>
function confirmDeactivate() {
  return confirm("Are you sure you want to deactivate your account? You can log in again anytime.");
}
function confirmDelete() {
  return confirm("⚠️ This will permanently delete your account and all posts. This action cannot be undone. Continue?");
}
function togglePassword(id, toggleId) {
  const input = document.getElementById(id);
  const toggle = document.getElementById(toggleId);
  if (input.type === "password") {
    input.type = "text";
    toggle.textContent = "Hide";
  } else {
    input.type = "password";
    toggle.textContent = "Show";
  }
}
</script>
</head>
<body>
<div class="wrap">

  <?php if ($notice): ?>
    <div style="background:#e6ffed;color:#0a6d2f;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($notice); ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="background:#ffecec;color:#9a1a3a;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="profile-head">
    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="avatar" class="avatar">
    <div class="summary">
      <h2><?php echo $display_name; ?> <span style="font-weight:400;color:#666;font-size:14px;">(<?php echo $display_role; ?>)</span></h2>
      <p><?php echo $display_email; ?> • <?php echo $display_phone ?: '—'; ?></p>
      <span class="status <?php echo strtolower($display_status); ?>"><?php echo ucfirst($display_status); ?></span>
      <?php if (!empty($display_bio)): ?>
        <div class="bio-box"><?php echo $display_bio; ?></div>
      <?php else: ?>
        <div class="small">You haven't added a bio yet. Add one in <strong>Edit Profile</strong>.</div>
      <?php endif; ?>
    </div>

    <div class="actions">
      <a class="btn" href="profile.php">View Profile</a>
      <form method="POST" class="inline" style="margin:0;">
        <button name="logout" class="btn secondary" type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div class="grid" style="margin-top:22px;">
    <div class="card">
      <h3>📝 Edit Profile</h3>
      <p>Change your display name, bio, or profile picture.</p>
      <a class="btn" href="edit_profile.php">Edit Profile</a>
      <div class="small">Name / Bio / Avatar</div>
    </div>

    <div class="card">
      <h3>📋 Personal Details</h3>
      <p>Edit your email address and phone number.</p>
      <a class="btn" href="edit_details.php">Edit Details</a>
      <div class="small">Email / Phone</div>
    </div>

    <div class="card">
      <h3>🔒 Change Password</h3>
      <p>Update your account password for security.</p>
      <a class="btn" href="change_password.php">Change Password</a>
      <div class="small">Current password required</div>
    </div>
  </div>

  <!-- Account Control Section -->
  <div class="grid" style="margin-top:22px;">
    <div class="card">
      <h3>⚙️ Deactivate Account</h3>
      <p>Temporarily deactivate your account. You can log back in anytime.</p>
      <form method="POST" onsubmit="return confirmDeactivate()">
        <input type="hidden" name="account_action" value="deactivate">
        <label>Enter Password to Deactivate:</label>
        <div class="pw-wrapper">
          <input type="password" name="password" id="deactivate_pw" required placeholder="Enter your password">
          <button type="button" class="pw-toggle" id="deactivate_toggle" onclick="togglePassword('deactivate_pw','deactivate_toggle')">Show</button>
        </div>
        <button type="submit" class="btn danger" style="margin-top:10px;width:100%;">Deactivate Account</button>
      </form>
    </div>

    <div class="card">
      <h3>❌ Delete Account Permanently</h3>
      <p>This will permanently remove your account and all data.</p>
      <form method="POST" onsubmit="return confirmDelete()">
        <input type="hidden" name="account_action" value="delete">
        <label>Enter Password to Delete:</label>
        <div class="pw-wrapper">
          <input type="password" name="password" id="delete_pw" required placeholder="Enter your password">
          <button type="button" class="pw-toggle" id="delete_toggle" onclick="togglePassword('delete_pw','delete_toggle')">Show</button>
        </div>
        <button type="submit" class="btn danger" style="background:#b30000;margin-top:10px;width:100%;">Delete Account Permanently</button>
      </form>
      <div class="small" style="margin-top:12px;">
        ⚠️ Deactivation just hides your account (status = pending).<br>
        ❌ Deletion erases everything permanently.
      </div>
    </div>
  </div>

  <div style="text-align:center; margin-top:18px;">
    <a href="profile.php" class="btn secondary" style="text-decoration:none;">← Back to Profile</a>
  </div>

</div>
</body>
</html>
