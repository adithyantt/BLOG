<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Blog</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        h2 { color: #333; }
        input[type="text"] { width: 60%; padding: 8px; }
        input[type="submit"] { padding: 8px 15px; }
        .result { margin-top: 20px; }
        .result a { display: block; padding: 8px; background: #fff; border-radius: 5px; margin-bottom: 10px; text-decoration: none; color: #007BFF; }
        .result a:hover { background: #e8e8e8; }
    </style>
</head>
<body>


<div class="result">
<?php
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = mysqli_real_escape_string($conn, $_GET['q']);

    $query = "SELECT post_id, title FROM posts WHERE title LIKE '%$q%' OR content LIKE '%$q%'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<a href='view.php?pid={$row['post_id']}'>" . htmlspecialchars($row['title']) . "</a>";
        }
    } else {
        echo "<p>No posts found for '<b>" . htmlspecialchars($q) . "</b>'</p>";
    }
}
?>
</div>

</body>
</html>

