<?php
session_start();
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

header('Content-Type: application/json');

$email = strtolower(trim($_POST['email'] ?? ''));
$name = trim($_POST['name'] ?? 'Student');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(["success" => false, "message" => "Please enter a valid email address first."]);
  exit;
}

$emailEsc = $conn->real_escape_string($email);
$chk = $conn->query("SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
if ($chk && $chk->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "This email is already registered. Please log in instead."]);
  exit;
}

$otp = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
$_SESSION['signup_otp'] = $otp;
$_SESSION['signup_otp_email'] = $email;
$_SESSION['signup_otp_expires'] = time() + 600;
$_SESSION['signup_otp_verified'] = false;

$sent = false;
if (function_exists('sendEmail')) {
  $safeName = htmlspecialchars($name !== '' ? $name : 'Student');
  $body = "<p>Hi $safeName,</p><p>Your Skill Connect email verification code for creating your profile is:</p><h2 style='letter-spacing:4px; color:#c6613f;'>$otp</h2><p>This code expires in 10 minutes.</p>";
  $sent = sendEmail($email, $name !== '' ? $name : 'Student', "Your Skill Connect Verification Code", $body);
}

if ($sent) {
  echo json_encode(["success" => true, "message" => "6-digit OTP sent to $email! Please check your inbox."]);
} else {
  echo json_encode(["success" => true, "message" => "OTP generated! (If email is delayed, use code: $otp)"]);
}
$conn->close();
?>
