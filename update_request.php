<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$id = intval($_GET['id']);
$action = $_GET['action'];
$currentUser = $conn->real_escape_string($_SESSION['profileName']);

$check = $conn->query("SELECT * FROM requests WHERE id = $id AND target_name = '$currentUser'");
if ($check->num_rows == 0) {
  echo "You are not authorized to update this request.";
  exit;
}

$status = ($action == "accept") ? "accepted" : "declined";
$conn->query("UPDATE requests SET status = '$status' WHERE id = $id");

header("Location: requests.php?view=received");
exit;
?>