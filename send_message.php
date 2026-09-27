<?php
// send_message.php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo "Security check failed.";
  exit;
}

$sender = $_SESSION['profileName'];
$receiver = $_POST['receiver_name'];
$message = trim($_POST['message']);

$s = $conn->real_escape_string($sender);
$r = $conn->real_escape_string($receiver);

$connectionCheck = $conn->query("SELECT id FROM requests WHERE status='accepted' AND ((requester_name='$s' AND target_name='$r') OR (requester_name='$r' AND target_name='$s'))");
if ($connectionCheck->num_rows == 0) {
  echo "You can only message people you're connected with.";
  exit;
}

if ($message != "") {
  $m = $conn->real_escape_string($message);
  $conn->query("INSERT INTO messages (sender_name, receiver_name, message) VALUES ('$s', '$r', '$m')");
}

header("Location: messages.php?with=" . urlencode($receiver));
exit;
?>