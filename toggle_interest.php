<?php
require_once __DIR__ . '/session_init.php';
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

$id = intval($_POST['id'] ?? 0);
$user = $_SESSION['profileName'];

$s = $conn->prepare("SELECT id FROM announcements WHERE id = ?");
$s->bind_param("i", $id);
$s->execute();
$exists = $s->get_result()->num_rows > 0;
$s->close();

if (!$exists) {
  echo json_encode(["success" => false, "message" => "This announcement no longer exists."]);
  exit;
}

$s = $conn->prepare("SELECT id FROM announcement_interest WHERE announcement_id = ? AND user_name = ?");
$s->bind_param("is", $id, $user);
$s->execute();
$already = $s->get_result()->num_rows > 0;
$s->close();

if ($already) {
  $s = $conn->prepare("DELETE FROM announcement_interest WHERE announcement_id = ? AND user_name = ?");
  $s->bind_param("is", $id, $user);
  $s->execute();
  $s->close();
  echo json_encode(["success" => true, "message" => "Removed from your interested list."]);
} else {
  $s = $conn->prepare("INSERT INTO announcement_interest (announcement_id, user_name) VALUES (?, ?)");
  $s->bind_param("is", $id, $user);
  $s->execute();
  $s->close();
  echo json_encode(["success" => true, "message" => "Marked as interested!"]);
}
$conn->close();
?>
