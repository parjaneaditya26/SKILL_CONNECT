<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "You must be logged in."]);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$id = intval($_POST['id']);
$currentUser = $conn->real_escape_string($_SESSION['profileName']);

$check = $conn->query("SELECT * FROM requests WHERE id = $id AND status = 'accepted' AND (requester_name = '$currentUser' OR target_name = '$currentUser')");

if ($check->num_rows == 0) {
  echo json_encode(["success" => false, "message" => "Not authorized."]);
  exit;
}
$check_row = $check->fetch_assoc();

$otherName = ($check_row['requester_name'] == $_SESSION['profileName']) ? $check_row['target_name'] : $check_row['requester_name'];
$on = $conn->real_escape_string($otherName);

$conn->query("DELETE FROM messages WHERE (sender_name = '$currentUser' AND receiver_name = '$on') OR (sender_name = '$on' AND receiver_name = '$currentUser')");
$conn->query("DELETE FROM requests WHERE id = $id");

echo json_encode(["success" => true, "message" => "Disconnected."]);
$conn->close();
?>
