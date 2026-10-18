<?php
session_start();
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

function renderProfileError($msg) {
  layoutStart("Profile Error", "Could Not Save Profile", "Please check the details below.");
  echo '<div class="empty-state"><h3>Oops!</h3><p>' . h($msg) . '</p><div style="margin-top:14px;"><a href="profile.php" class="connectBtn" style="text-decoration:none; display:inline-block;">Back to Profile</a> <a href="login.php" class="btn-outline" style="margin-left:8px;">Log In</a></div></div>';
  layoutEnd();
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  renderProfileError("Security check failed. Please go back and try again.");
}

$loggedIn = isset($_SESSION['profileName']);

if ($loggedIn) {
  $name = $_SESSION['profileName'];
} else {
  $name = trim($_POST['name'] ?? '');
}

$mobile = trim($_POST['mobile'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$branch = $_POST['branch'] ?? '';
$year_of_study = $_POST['year_of_study'] ?? '';
$teachSkill = trim($_POST['teachSkill'] ?? '');
$learnSkill = trim($_POST['learnSkill'] ?? '');
$postedOtp = trim($_POST['signup_otp'] ?? '');

if ($teachSkill == "") { $teachSkill = "Nothing yet"; }
if ($learnSkill == "") { $learnSkill = "Nothing yet"; }

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  renderProfileError("Please enter a valid email address.");
}

$nameEsc = $conn->real_escape_string($name);
$mobileEsc = $conn->real_escape_string($mobile);
$emailEsc = $conn->real_escape_string($email);
$branchEsc = $conn->real_escape_string($branch);
$yearEsc = $conn->real_escape_string($year_of_study);
$teachEsc = $conn->real_escape_string($teachSkill);
$learnEsc = $conn->real_escape_string($learnSkill);

if ($loggedIn) {
  if ($password != "") {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET mobile='$mobileEsc', password='$hashed', branch='$branchEsc', year_of_study='$yearEsc', teach_skill='$teachEsc', learn_skill='$learnEsc' WHERE name='$nameEsc'";
  } else {
    $sql = "UPDATE users SET mobile='$mobileEsc', branch='$branchEsc', year_of_study='$yearEsc', teach_skill='$teachEsc', learn_skill='$learnEsc' WHERE name='$nameEsc'";
  }
} else {
  // Check OTP verification at profile creation
  $sessionEmail = strtolower($_SESSION['signup_otp_email'] ?? '');
  $sessionOtp = (string)($_SESSION['signup_otp'] ?? '');
  $sessionExp = intval($_SESSION['signup_otp_expires'] ?? 0);
  $alreadyVerified = !empty($_SESSION['signup_otp_verified']) && ($sessionEmail === $email);
  $matchesNow = ($sessionEmail === $email) && ($sessionOtp !== '') && (time() <= $sessionExp) && hash_equals($sessionOtp, $postedOtp);

  if (!$alreadyVerified && !$matchesNow) {
    renderProfileError("Please verify your email address using the 6-digit OTP before creating your profile.");
  }

  $check = $conn->query("SELECT id FROM users WHERE name = '$nameEsc'");
  if ($check && $check->num_rows > 0) {
    renderProfileError("That name is already taken. Please log in instead, or choose a different name.");
  }

  $emailCheck = $conn->query("SELECT id FROM users WHERE email = '$emailEsc'");
  if ($emailCheck && $emailCheck->num_rows > 0) {
    renderProfileError("That email is already registered to another profile. Please log in instead, or use a different email.");
  }

  $hashed = password_hash($password, PASSWORD_DEFAULT);
  $sql = "INSERT INTO users (name, mobile, email, password, branch, year_of_study, teach_skill, learn_skill, verified) VALUES ('$nameEsc', '$mobileEsc', '$emailEsc', '$hashed', '$branchEsc', '$yearEsc', '$teachEsc', '$learnEsc', 1)";
}

if ($conn->query($sql) === TRUE) {
  $_SESSION['profileName'] = $name;
  unset($_SESSION['signup_otp'], $_SESSION['signup_otp_email'], $_SESSION['signup_otp_expires'], $_SESSION['signup_otp_verified']);
  $conn->close();
  if ($loggedIn) {
    header("Location: profile.php?saved=1");
  } else {
    header("Location: index.php");
  }
  exit;
} else {
  $err = $conn->error;
  $conn->close();
  renderProfileError("Database error: " . $err);
}
?>
