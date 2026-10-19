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

$user = $_SESSION['profileName'];
$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$s = $conn->prepare("SELECT id FROM help_posts WHERE id = ? AND author_name = ?");
$s->bind_param("is", $id, $user);
$s->execute();
$mine = $s->get_result()->num_rows > 0;
$s->close();

if (!$mine) {
  echo json_encode(["success" => false, "message" => "You can only change your own posts."]);
  exit;
}

if ($action === 'done' || $action === 'reopen') {
  $status = ($action === 'done') ? 'done' : 'open';
  $s = $conn->prepare("UPDATE help_posts SET status = ? WHERE id = ?");
  $s->bind_param("si", $status, $id);
  $s->execute();
  $s->close();
  echo json_encode(["success" => true, "message" => $status === 'done' ? "Marked as done." : "Post reopened."]);
} elseif ($action === 'delete') {
  $s = $conn->prepare("DELETE FROM help_responses WHERE post_id = ?");
  $s->bind_param("i", $id); $s->execute(); $s->close();
  $s = $conn->prepare("DELETE FROM help_posts WHERE id = ?");
  $s->bind_param("i", $id); $s->execute(); $s->close();
  echo json_encode(["success" => true, "message" => "Post deleted."]);
} else {
  echo json_encode(["success" => false, "message" => "Unknown action."]);
}
$conn->close();
?>
