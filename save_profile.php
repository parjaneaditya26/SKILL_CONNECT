<?php
session_start();
include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo "<p>Security check failed. Please try again.</p><a href='profile.php'>Go Back</a>";
  exit;
}

$loggedIn = isset($_SESSION['profileName']);

if ($loggedIn) {
  $name = $_SESSION['profileName'];
} else {
  $name = trim($_POST['name']);
}

$mobile = trim($_POST['mobile']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$teachSkill = trim($_POST['teachSkill']);
$learnSkill = trim($_POST['learnSkill']);
$category = $_POST['category'];

if ($teachSkill == "") { $teachSkill = "Nothing yet"; }
if ($learnSkill == "") { $learnSkill = "Nothing yet"; }

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo "<p>Please enter a valid email address.</p>";
  echo "<a href='profile.php'>Go Back</a>";
  exit;
}

$name = $conn->real_escape_string($name);
$mobile = $conn->real_escape_string($mobile);
$email = $conn->real_escape_string($email);
$teachSkill = $conn->real_escape_string($teachSkill);
$learnSkill = $conn->real_escape_string($learnSkill);
$category = $conn->real_escape_string($category);

if ($loggedIn) {
  if ($password != "") {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET mobile='$mobile', email='$email', password='$hashed', teach_skill='$teachSkill', learn_skill='$learnSkill', category='$category' WHERE name='$name'";
  } else {
    $sql = "UPDATE users SET mobile='$mobile', email='$email', teach_skill='$teachSkill', learn_skill='$learnSkill', category='$category' WHERE name='$name'";
  }
  $message = "Profile updated successfully!";
} else {
  $check = $conn->query("SELECT id FROM users WHERE name = '$name'");
  if ($check->num_rows > 0) {
    echo "<p>That name is already taken. Please log in instead, or choose a different name.</p>";
    echo "<a href='login.php'>Log In</a> | <a href='profile.php'>Try Again</a>";
    $conn->close();
    exit;
  }

  $emailCheck = $conn->query("SELECT id FROM users WHERE email = '$email'");
  if ($emailCheck->num_rows > 0) {
    echo "<p>That email is already registered to another profile. Please log in instead, or use a different email.</p>";
    echo "<a href='login.php'>Log In</a> | <a href='profile.php'>Try Again</a>";
    $conn->close();
    exit;
  }

  $hashed = password_hash($password, PASSWORD_DEFAULT);
  $verifyToken = bin2hex(random_bytes(16));
  $vt = $conn->real_escape_string($verifyToken);
  $sql = "INSERT INTO users (name, mobile, email, password, teach_skill, learn_skill, category, verify_token) VALUES ('$name', '$mobile', '$email', '$hashed', '$teachSkill', '$learnSkill', '$category', '$vt')";
  $message = "Profile created successfully! You are now logged in. Check your email to verify your account.";
}

if ($conn->query($sql) === TRUE) {
  $_SESSION['profileName'] = $name;

  if (!$loggedIn && function_exists('sendEmail')) {
    $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=$verifyToken";
    sendEmail($email, $name, "Verify Your Skill Connect Email", "<p>Hi " . htmlspecialchars($name) . ",</p><p>Welcome to Skill Connect! Click below to verify your email:</p><p><a href='$verifyLink'>Verify Email</a></p>");
  }

  echo "<p>$message</p>";
  echo "<a href='browse.php'>Go to Browse Skills</a>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>