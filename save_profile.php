<?php
session_start();
include 'db_connect.php';

$name = $_POST['name'];
$mobile = $_POST['mobile'];
$password = $_POST['password'];
$teachSkill = $_POST['teachSkill'];
$learnSkill = $_POST['learnSkill'];
$category = $_POST['category'];

if ($teachSkill == "") { $teachSkill = "Nothing yet"; }
if ($learnSkill == "") { $learnSkill = "Nothing yet"; }

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->query("SELECT id FROM users WHERE name = '$name'");

if ($check->num_rows > 0) {
  $sql = "UPDATE users SET mobile = '$mobile', teach_skill = '$teachSkill', learn_skill = '$learnSkill', category = '$category' WHERE name = '$name'";
  $message = "Profile updated successfully!";
} else {
  $sql = "INSERT INTO users (name, mobile, password, teach_skill, learn_skill, category) VALUES ('$name', '$mobile', '$hashedPassword', '$teachSkill', '$learnSkill', '$category')";
  $message = "Profile created successfully!";
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