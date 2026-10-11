<?php
session_start();
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

$reporter = $conn->real_escape_string($_SESSION['profileName']);
$target_id = intval($_POST['target_id']);
$target_name = $conn->real_escape_string($_POST['target_name']);
$reason = $conn->real_escape_string($_POST['reason']);
$details = $conn->real_escape_string($_POST['details']);

$conn->query("INSERT INTO reports (reporter_name, target_id, target_name, reason, details) VALUES ('$reporter', $target_id, '$target_name', '$reason', '$details')");
echo json_encode(["success" => true, "message" => "Report submitted. Thank you."]);
$conn->close();
?>
