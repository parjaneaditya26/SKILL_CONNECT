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
$branch = $_POST['branch'];
$year_of_study = $_POST['year_of_study'];
$teachSkill = trim($_POST['teachSkill']);
$learnSkill = trim($_POST['learnSkill']);

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
$branch = $conn->real_escape_string($branch);
$year_of_study = $conn->real_escape_string($year_of_study);
$teachSkill = $conn->real_escape_string($teachSkill);
$learnSkill = $conn->real_escape_string($learnSkill);

if ($loggedIn) {
  if ($password != "") {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET mobile='$mobile', email='$email', password='$hashed', branch='$branch', year_of_study='$year_of_study', teach_skill='$teachSkill', learn_skill='$learnSkill' WHERE name='$name'";
  } else {
    $sql = "UPDATE users SET mobile='$mobile', email='$email', branch='$branch', year_of_study='$year_of_study', teach_skill='$teachSkill', learn_skill='$learnSkill' WHERE name='$name'";
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
  $sql = "INSERT INTO users (name, mobile, email, password, branch, year_of_study, teach_skill, learn_skill) VALUES ('$name', '$mobile', '$email', '$hashed', '$branch', '$year_of_study', '$teachSkill', '$learnSkill')";
  $message = "Profile created successfully! You are now logged in.";
}

if ($conn->query($sql) === TRUE) {
  $_SESSION['profileName'] = $name;
  echo "<p>$message</p>";
  echo "<a href='browse.php'>Go to Browse Skills</a>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>