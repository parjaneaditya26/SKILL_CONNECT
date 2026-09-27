<?php
// update_request.php
session_start();
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

if (!csrf_verify($_GET['csrf'] ?? '')) {
  echo "Security check failed.";
  exit;
}

$id = intval($_GET['id']);
$action = $_GET['action'];
$currentUser = $conn->real_escape_string($_SESSION['profileName']);

$check = $conn->query("SELECT * FROM requests WHERE id = $id AND target_name = '$currentUser'");
if ($check->num_rows == 0) {
  echo "You are not authorized to update this request.";
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
    $extraNote = ($status == "accepted") ? "<p>You can now see each other's contact details on Skill Connect.</p>" : "";
    sendEmail(
      $emailRow['email'],
      $requesterName,
      "Your Skill Connect request was " . $status,
      "<p>Hi " . htmlspecialchars($requesterName) . ",</p><p><strong>" . htmlspecialchars($_SESSION['profileName']) . "</strong> has <strong>$status</strong> your connection request.</p>" . $extraNote
    );
  }
}

header("Location: requests.php?view=received");
exit;
?>