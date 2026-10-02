<?php
session_start();
include 'db_connect.php';
@include 'mailer.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$name = $conn->real_escape_string($_SESSION['profileName']);
$result = $conn->query("SELECT * FROM users WHERE name = '$name'");
$user = $result->fetch_assoc();

if ($user['verified'] == 1) {
  echo "<p>Your email is already verified.</p><a href='profile.php'>Back to Profile</a>";
  exit;
}

$otp = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
$o = $conn->real_escape_string($otp);
$conn->query("UPDATE users SET verify_otp = '$o', verify_otp_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = {$user['id']}");

if (function_exists('sendEmail')) {
  sendEmail($user['email'], $user['name'], "Your Skill Connect Verification Code", "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Your new verification code is:</p><h2 style='letter-spacing:4px;'>$otp</h2><p>This code expires in 10 minutes.</p>");
}

echo "<p>A new verification code has been sent! Check your inbox.</p>";
echo "<a href='verify_otp.php?email=" . urlencode($user['email']) . "' class='connectBtn' style='display:inline-block; text-decoration:none; margin-top:10px;'>Enter Code</a>";
?>