<?php
include 'db_connect.php';

$id = intval($_POST['id']);

$conn->query("DELETE FROM users WHERE id = $id");
// Also clean up any requests and ratings tied to this user, so old data doesn't linger
$conn->query("DELETE FROM requests WHERE target_id = $id");
$conn->query("DELETE FROM ratings WHERE target_id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>