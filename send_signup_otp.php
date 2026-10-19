<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

header('Content-Type: application/json');

if (!csrf_verify_request()) {
  echo json_encode(["success" => false, "message" => "Security check failed. Please refresh the page and try again."]);
  exit;
}

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

// Throttle: 30 seconds between sends, max 5 sends per hour per browser session
$now = time();
if (($now - intval($_SESSION['signup_otp_last_sent'] ?? 0)) < 30) {
  echo json_encode(["success" => false, "message" => "Please wait 30 seconds before requesting another code."]);
  exit;
}
$_SESSION['signup_otp_sends'] = array_values(array_filter($_SESSION['signup_otp_sends'] ?? [], function ($t) use ($now) { return $t > $now - 3600; }));
if (count($_SESSION['signup_otp_sends']) >= 5) {
  echo json_encode(["success" => false, "message" => "Too many codes requested. Please try again in an hour."]);
  exit;
}
$_SESSION['signup_otp_sends'][] = $now;
$_SESSION['signup_otp_last_sent'] = $now;

$otp = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
$_SESSION['signup_otp_attempts'] = 0;
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
} elseif (app_is_local()) {
  // Developer convenience on localhost only: show the code if mail is not configured
  echo json_encode(["success" => true, "message" => "OTP generated! (Local mode, email not sent. Use code: $otp)"]);
} else {
  // On the live site never reveal the code. Without email delivery it would defeat the verification.
  unset($_SESSION['signup_otp'], $_SESSION['signup_otp_email'], $_SESSION['signup_otp_expires']);
  echo json_encode(["success" => false, "message" => "We could not send the email right now. Please try again later."]);
}
$conn->close();
?>
