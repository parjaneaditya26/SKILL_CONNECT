<?php
include 'db_connect.php';

$id = $_GET['id'];
$action = $_GET['action']; // "accept" or "decline"
$name = $_GET['name'];

if ($action == "accept") {
  $status = "accepted";
} else {
  $status = "declined";
}

$sql = "UPDATE requests SET status = '$status' WHERE id = $id";
$conn->query($sql);

header("Location: requests.php?name=" . urlencode($name));
exit;
?>