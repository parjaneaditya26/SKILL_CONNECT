<?php
require_once __DIR__ . '/session_init.php';
header('Content-Type: application/json');

if (!csrf_verify_request()) {
  echo json_encode(["success" => false, "message" => "Security check failed. Please refresh the page and try again."]);
  exit;
}

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

if (intval($_SESSION['signup_otp_attempts'] ?? 0) >= 5) {
  unset($_SESSION['signup_otp'], $_SESSION['signup_otp_email'], $_SESSION['signup_otp_expires']);
  echo json_encode(["success" => false, "message" => "Too many wrong attempts. Please click 'Send OTP' to get a new code."]);
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
  $_SESSION['signup_otp_attempts'] = intval($_SESSION['signup_otp_attempts'] ?? 0) + 1;
  echo json_encode(["success" => false, "message" => "Invalid 6-digit OTP. Please check your email and try again."]);
}
?>
