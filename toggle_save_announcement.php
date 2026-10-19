<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "Please log in to bookmark announcements."]);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$id = intval($_POST['id'] ?? 0);
$user = $_SESSION['profileName'];

$s = $conn->prepare("SELECT id FROM announcements WHERE id = ?");
if (!$s) {
  echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
  exit;
}
$s->bind_param("i", $id);
$s->execute();
$r = $s->get_result();
$exists = ($r && $r->num_rows > 0);
$s->close();

if (!$exists) {
  echo json_encode(["success" => false, "message" => "Announcement not found."]);
  exit;
}

$already = false;
$s = $conn->prepare("SELECT id FROM saved_announcements WHERE announcement_id = ? AND user_name = ?");
if ($s) {
  $s->bind_param("is", $id, $user);
  $s->execute();
  $r = $s->get_result();
  $already = ($r && $r->num_rows > 0);
  $s->close();
}

if ($already) {
  $s = $conn->prepare("DELETE FROM saved_announcements WHERE announcement_id = ? AND user_name = ?");
  if ($s) {
    $s->bind_param("is", $id, $user);
    $s->execute();
    $s->close();
  }
  echo json_encode(["success" => true, "message" => "Removed from Saved Notices."]);
} else {
  $s = $conn->prepare("INSERT INTO saved_announcements (announcement_id, user_name) VALUES (?, ?)");
  if ($s) {
    $s->bind_param("is", $id, $user);
    $s->execute();
    $s->close();
  }
  echo json_encode(["success" => true, "message" => "Saved to your Saved Notices! 🔖"]);
}
$conn->close();
?>
