<?php
// disconnect.php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

if (!csrf_verify($_GET['csrf'] ?? '')) {
  echo "Security check failed.";
  exit;
}

$id = intval($_GET['id']);
$currentUser = $conn->real_escape_string($_SESSION['profileName']);
$view = isset($_GET['view']) ? $_GET['view'] : 'received';

$check = $conn->query("SELECT * FROM requests WHERE id = $id AND status = 'accepted' AND (requester_name = '$currentUser' OR target_name = '$currentUser')");

if ($check->num_rows == 0) {
  echo "You are not authorized to disconnect this request.";
  exit;
}

$conn->query("DELETE FROM requests WHERE id = $id");

header("Location: requests.php?view=" . $view);
exit;
?>