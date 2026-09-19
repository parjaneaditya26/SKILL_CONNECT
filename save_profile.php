<?php
include 'db_connect.php';

$name = $_POST['name'];
$mobile = $_POST['mobile'];
$teachSkill = $_POST['teachSkill'];
$learnSkill = $_POST['learnSkill'];
$category = $_POST['category'];

$check = $conn->query("SELECT id FROM users WHERE name = '$name'");

if ($check->num_rows > 0) {
  $sql = "UPDATE users SET mobile = '$mobile', teach_skill = '$teachSkill', learn_skill = '$learnSkill', category = '$category' WHERE name = '$name'";
  $message = "Profile updated successfully!";
} else {
  $sql = "INSERT INTO users (name, mobile, teach_skill, learn_skill, category) VALUES ('$name', '$mobile', '$teachSkill', '$learnSkill', '$category')";
  $message = "Profile created successfully!";
}

if ($conn->query($sql) === TRUE) {
  echo "<p>$message</p>";
  echo "<a href='profile.html'>Back to Profile</a>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>