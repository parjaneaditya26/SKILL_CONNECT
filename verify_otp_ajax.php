<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "You must be logged in."]);
  exit;
}

$otp = isset($_POST['otp']) ? trim($_POST['otp']) : "";
if ($otp == "") {
  echo json_encode(["success" => false, "message" => "Please enter the 6-digit code."]);
  exit;
}

$name = $conn->real_escape_string($_SESSION['profileName']);
$o = $conn->real_escape_string($otp);

$result = $conn->query("SELECT id FROM users WHERE name = '$name' AND verify_otp = '$o' AND verify_otp_expires > NOW()");

if ($result->num_rows > 0) {
  $user = $result->fetch_assoc();
  $conn->query("UPDATE users SET verified = 1, verify_otp = NULL, verify_otp_expires = NULL WHERE id = {$user['id']}");
  echo json_encode(["success" => true, "message" => "Email verified!", "redirect" => "profile.php"]);
} else {
  echo json_encode(["success" => false, "message" => "Invalid or expired code. Send a new code and try again."]);
}
$conn->close();
?>
