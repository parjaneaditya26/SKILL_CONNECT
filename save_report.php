<?php
// save_report.php
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

$reporter = $conn->real_escape_string($_SESSION['profileName']);
$target_id = intval($_POST['target_id']);
$target_name = $conn->real_escape_string($_POST['target_name']);
$reason = $conn->real_escape_string($_POST['reason']);
$details = $conn->real_escape_string($_POST['details']);

$conn->query("INSERT INTO reports (reporter_name, target_id, target_name, reason, details) VALUES ('$reporter', $target_id, '$target_name', '$reason', '$details')");

echo "<p>Thank you, your report has been submitted.</p>";
echo "<a href='browse.php'>Back to Browse Skills</a>";
?>