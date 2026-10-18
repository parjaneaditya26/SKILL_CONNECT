<?php
session_start();
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required. Please log in with admin password."]);
  exit;
}

include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'delete') {
  $s = $conn->prepare("DELETE FROM announcement_interest WHERE announcement_id = ?");
  if ($s) { $s->bind_param("i", $id); $s->execute(); $s->close(); }
  $s = $conn->prepare("DELETE FROM saved_announcements WHERE announcement_id = ?");
  if ($s) { $s->bind_param("i", $id); $s->execute(); $s->close(); }
  $s = $conn->prepare("DELETE FROM announcements WHERE id = ?");
  if ($s) { $s->bind_param("i", $id); $s->execute(); $s->close(); }
  echo json_encode(["success" => true, "message" => "Announcement deleted."]);
} elseif ($action === 'pin' || $action === 'unpin') {
  $val = ($action === 'pin') ? 1 : 0;
  $s = $conn->prepare("UPDATE announcements SET pinned = ? WHERE id = ?");
  if ($s) { $s->bind_param("ii", $val, $id); $s->execute(); $s->close(); }
  echo json_encode(["success" => true, "message" => $val ? "Announcement pinned." : "Announcement unpinned."]);
} else {
  echo json_encode(["success" => false, "message" => "Unknown action."]);
}
$conn->close();
?>
