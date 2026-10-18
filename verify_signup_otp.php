<?php
session_start();
header('Content-Type: application/json');

$email = strtolower(trim($_POST['email'] ?? ''));
$otp = trim($_POST['otp'] ?? '');

if ($email === '' || $otp === '') {
  echo json_encode(["success" => false, "message" => "Please enter both your email and the 6-digit OTP."]);
  exit;
}

if (
  empty($_SESSION['signup_otp']) ||
  empty($_SESSION['signup_otp_email']) ||
  empty($_SESSION['signup_otp_expires'])
) {
  echo json_encode(["success" => false, "message" => "Please click 'Send OTP' first to get a verification code."]);
  exit;
}

if (time() > intval($_SESSION['signup_otp_expires'])) {
  echo json_encode(["success" => false, "message" => "OTP has expired. Please click 'Send OTP' again."]);
  exit;
}

if (strtolower($_SESSION['signup_otp_email']) !== $email) {
  echo json_encode(["success" => false, "message" => "Email address changed. Please click 'Send OTP' for the new email."]);
  exit;
}

if (hash_equals((string)$_SESSION['signup_otp'], $otp)) {
  $_SESSION['signup_otp_verified'] = true;
  echo json_encode(["success" => true, "message" => "Email verified by OTP! You can now click Create Verified Profile."]);
} else {
  echo json_encode(["success" => false, "message" => "Invalid 6-digit OTP. Please check your email and try again."]);
}
?>
