<?php
include 'inc/config.php';
$res = $conn->query("SHOW COLUMNS FROM postingan");
while($row = $res->fetch_assoc()) echo $row['Field']." | ";
?>
