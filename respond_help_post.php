<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

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
$postId = intval($_POST['post_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (textLen($message) < 2 || textLen($message) > 300) {
  echo json_encode(["success" => false, "message" => "Message must be 2 to 300 characters."]);
  exit;
}

$s = $conn->prepare("SELECT * FROM help_posts WHERE id = ?");
$s->bind_param("i", $postId);
$s->execute();
$post = $s->get_result()->fetch_assoc();
$s->close();

if (!$post) {
  echo json_encode(["success" => false, "message" => "This post no longer exists."]);
  exit;
}
if ($post['author_name'] === $user) {
  echo json_encode(["success" => false, "message" => "You can't respond to your own post."]);
  exit;
}
if ($post['status'] !== 'open') {
  echo json_encode(["success" => false, "message" => "This post is already closed."]);
  exit;
}

$s = $conn->prepare("SELECT id FROM help_responses WHERE post_id = ? AND responder_name = ?");
$s->bind_param("is", $postId, $user);
$s->execute();
$dup = $s->get_result()->num_rows > 0;
$s->close();
if ($dup) {
  echo json_encode(["success" => false, "message" => "You've already responded to this post."]);
  exit;
}

$s = $conn->prepare("INSERT INTO help_responses (post_id, responder_name, message) VALUES (?, ?, ?)");
$s->bind_param("iss", $postId, $user, $message);
$ok = $s->execute();
$s->close();

if (!$ok) {
  echo json_encode(["success" => false, "message" => "Could not send your response."]);
  exit;
}

if (function_exists('sendEmail')) {
  $s = $conn->prepare("SELECT email FROM users WHERE name = ?");
  $s->bind_param("s", $post['author_name']);
  $s->execute();
  $row = $s->get_result()->fetch_assoc();
  $s->close();
  if ($row && $row['email']) {
    sendEmail($row['email'], $post['author_name'], "Someone responded to your post - Skill Connect",
      "<p>Hi " . h($post['author_name']) . ",</p><p><strong>" . h($user) . "</strong> responded to your post <em>" . h($post['title']) . "</em>:</p><p>" . h($message) . "</p><p>Open the Help Board on Skill Connect to reply.</p>");
  }
}

echo json_encode(["success" => true, "message" => "Response sent! The author has been notified."]);
$conn->close();
?>
