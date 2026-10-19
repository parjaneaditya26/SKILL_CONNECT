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

$id = intval($_POST['id']);
$action = $_POST['action'];
$currentUser = $conn->real_escape_string($_SESSION['profileName']);

$check = $conn->query("SELECT * FROM requests WHERE id = $id AND target_name = '$currentUser'");
if ($check->num_rows == 0) {
  echo json_encode(["success" => false, "message" => "Not authorized."]);
  exit;
}
$request = $check->fetch_assoc();

$status = ($action == "accept") ? "accepted" : "declined";
$conn->query("UPDATE requests SET status = '$status' WHERE id = $id");

if (function_exists('sendEmail')) {
  $requesterName = $request['requester_name'];
  $rn = $conn->real_escape_string($requesterName);
  $emailResult = $conn->query("SELECT email FROM users WHERE name = '$rn'");
  if ($emailResult && $emailResult->num_rows > 0) {
    $emailRow = $emailResult->fetch_assoc();
    sendEmail($emailRow['email'], $requesterName, "Your Skill Connect request was " . $status, "<p>Hi " . htmlspecialchars($requesterName) . ",</p><p><strong>" . htmlspecialchars($_SESSION['profileName']) . "</strong> has <strong>$status</strong> your connection request.</p>");
  }
}

echo json_encode(["success" => true, "message" => "Request $status!"]);
$conn->close();
?>
