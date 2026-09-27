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

$token = bin2hex(random_bytes(16));
$t = $conn->real_escape_string($token);
$conn->query("UPDATE users SET verify_token = '$t' WHERE id = {$user['id']}");

if (function_exists('sendEmail')) {
  $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=$token";
  sendEmail($user['email'], $user['name'], "Verify Your Skill Connect Email", "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Click below to verify your email:</p><p><a href='$verifyLink'>Verify Email</a></p>");
}

echo "<p>Verification email sent! Check your inbox.</p><a href='profile.php'>Back to Profile</a>";
?>