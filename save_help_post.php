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
$type = $_POST['type'] ?? '';
$title = trim($_POST['title'] ?? '');
$details = trim($_POST['details'] ?? '');

if (!in_array($type, ['teammates', 'help'], true)) {
  echo json_encode(["success" => false, "message" => "Please choose what you need."]);
  exit;
}
if (textLen($title) < 3 || textLen($title) > 150) {
  echo json_encode(["success" => false, "message" => "Title must be 3 to 150 characters."]);
  exit;
}
if (textLen($details) < 5 || textLen($details) > 1000) {
  echo json_encode(["success" => false, "message" => "Details must be 5 to 1000 characters."]);
  exit;
}

$s = $conn->prepare("SELECT COUNT(*) AS c FROM help_posts WHERE author_name = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$s->bind_param("s", $user);
$s->execute();
$recent = $s->get_result()->fetch_assoc()['c'];
$s->close();
if ($recent >= 5) {
  echo json_encode(["success" => false, "message" => "You've posted 5 times today. Please try again tomorrow."]);
  exit;
}

$s = $conn->prepare("INSERT INTO help_posts (author_name, type, title, details) VALUES (?, ?, ?, ?)");
$s->bind_param("ssss", $user, $type, $title, $details);
$ok = $s->execute();
$s->close();

echo json_encode($ok ? ["success" => true, "message" => "Posted!"] : ["success" => false, "message" => "Could not save your post."]);
$conn->close();
?>
