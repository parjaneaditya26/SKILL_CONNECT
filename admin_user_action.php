<?php
require_once __DIR__ . '/session_init.php';
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required."]);
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

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($action === 'toggle_verify' && $id > 0) {
  $r = $conn->query("SELECT verified, name FROM users WHERE id = $id");
  if ($r && $r->num_rows > 0) {
    $u = $r->fetch_assoc();
    $newVal = $u['verified'] ? 0 : 1;
    $conn->query("UPDATE users SET verified = $newVal WHERE id = $id");
    echo json_encode(["success" => true, "message" => $newVal ? "Verified " . $u['name'] . "!" : "Marked " . $u['name'] . " as unverified."]);
  } else {
    echo json_encode(["success" => false, "message" => "User not found."]);
  }
} elseif ($action === 'verify_all') {
  $conn->query("UPDATE users SET verified = 1 WHERE verified = 0");
  $affected = $conn->affected_rows;
  echo json_encode(["success" => true, "message" => $affected > 0 ? "Verified $affected student account(s)!" : "All students are already verified!"]);
} elseif ($action === 'delete_help_post' && $id > 0) {
  $conn->query("DELETE FROM help_responses WHERE post_id = $id");
  $conn->query("DELETE FROM help_posts WHERE id = $id");
  echo json_encode(["success" => true, "message" => "Help board post removed."]);
} elseif ($action === 'toggle_help_post' && $id > 0) {
  $r = $conn->query("SELECT status FROM help_posts WHERE id = $id");
  if ($r && $r->num_rows > 0) {
    $row = $r->fetch_assoc();
    $next = ($row['status'] === 'open') ? 'done' : 'open';
    $conn->query("UPDATE help_posts SET status = '$next' WHERE id = $id");
    echo json_encode(["success" => true, "message" => "Help post marked as $next."]);
  } else {
    echo json_encode(["success" => false, "message" => "Post not found."]);
  }
} else {
  echo json_encode(["success" => false, "message" => "Unknown admin action."]);
}
$conn->close();
?>
