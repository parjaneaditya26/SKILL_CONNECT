<?php
session_start();
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

$requester = $_SESSION['profileName'];
$target_id = intval($_POST['target_id']);
$target_name = $_POST['target_name'];

if ($requester == $target_name) {
  echo json_encode(["success" => false, "message" => "You can't connect with yourself!"]);
  exit;
}

$req = $conn->real_escape_string($requester);

$rateCheck = $conn->query("SELECT COUNT(*) as total FROM requests WHERE requester_name = '$req' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$rateRow = $rateCheck->fetch_assoc();
if ($rateRow['total'] >= 10) {
  echo json_encode(["success" => false, "message" => "Too many requests sent today. Try again tomorrow."]);
  exit;
}

$check = $conn->query("SELECT id FROM requests WHERE requester_name = '$req' AND target_id = $target_id AND status IN ('pending','accepted')");

if ($check->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "You've already sent a request to $target_name."]);
} else {
  $tn = $conn->real_escape_string($target_name);
  $sql = "INSERT INTO requests (requester_name, target_id, target_name) VALUES ('$req', $target_id, '$tn')";
  if ($conn->query($sql) === TRUE) {
    if (function_exists('sendEmail')) {
      $emailResult = $conn->query("SELECT email FROM users WHERE id = $target_id");
      if ($emailResult && $emailResult->num_rows > 0) {
        $emailRow = $emailResult->fetch_assoc();
        sendEmail($emailRow['email'], $target_name, "New Connection Request - Skill Connect", "<p>Hi " . htmlspecialchars($target_name) . ",</p><p><strong>" . htmlspecialchars($requester) . "</strong> wants to connect with you on Skill Connect!</p>");
      }
    }
    echo json_encode(["success" => true, "message" => "Connection request sent to $target_name!"]);
  } else {
    echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
  }
}

$conn->close();
?>
