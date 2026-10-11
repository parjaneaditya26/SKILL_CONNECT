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

$name = $conn->real_escape_string($_SESSION['profileName']);
$result = $conn->query("SELECT * FROM users WHERE name = '$name'");
$user = $result->fetch_assoc();

if ($user['verified'] == 1) {
  echo json_encode(["success" => false, "message" => "Already verified."]);
  exit;
}

$otp = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
$o = $conn->real_escape_string($otp);
$conn->query("UPDATE users SET verify_otp = '$o', verify_otp_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = {$user['id']}");

if (function_exists('sendEmail')) {
  sendEmail($user['email'], $user['name'], "Your Skill Connect Verification Code", "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Your code is:</p><h2 style='letter-spacing:4px;'>$otp</h2><p>Expires in 10 minutes.</p>");
}

echo json_encode(["success" => true, "message" => "Code sent! Check your email."]);
$conn->close();
?>
