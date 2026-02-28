<?php
include "config.php";
header('Content-Type: application/json');

$result = mysqli_query($conn, "SELECT DISTINCT category FROM posts ORDER BY category ASC");
$cats = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cats[] = $row['category'];
}
//fetch categorie
echo json_encode($cats);
