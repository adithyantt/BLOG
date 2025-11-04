<?php
include "../config.php";
session_start();

// Ensure only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['uid'];
    $message = trim($_POST['message']);

    if ($uid > 0 && $message !== "") {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("is", $uid, $message);

        if ($stmt->execute()) {
            echo "<script>alert('Message sent successfully!'); window.location.href='manage_users.php';</script>";
            exit;
        } else {
            echo "<script>alert('Failed to send message.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Message User | NoCapPress Admin</title>
  <link rel="stylesheet" href="admin_style.css">
  <style>
    form { max-width: 500px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 8px; }
    textarea { width: 100%; height: 120px; padding: 10px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 15px; }
    button { padding: 10px 20px; border: none; border-radius: 6px; background:#3b82f6; color: #fff; cursor: pointer; }
  </style>
</head>
<body>
  <div class="admin-container">
    <main class="main-content">
      <h2>Send Message to User</h2>
      <form method="POST">
        <input type="hidden" name="uid" value="<?php echo $uid; ?>">
        <label for="message">Message:</label><br>
        <textarea name="message" placeholder="Type your message to the user..." required></textarea>
        <br>
        <button type="submit">Send Message</button>
      </form>
    </main>
  </div>
</body>
</html>
