<?php
include 'db_connect.php';

$name = $_POST['name'];
$password = $_POST['password'];

$result = $conn->query("SELECT id, password FROM users WHERE name = '$name'");

if ($result->num_rows == 0) {
  echo "<p>No profile found with that name.</p>";
} else {
  $user = $result->fetch_assoc();
  if (password_verify($password, $user['password'])) {
    $conn->query("DELETE FROM users WHERE name = '$name'");
    echo "<p>Your profile has been deleted.</p>";
  } else {
    echo "<p>Incorrect password. Profile not deleted.</p>";
  }
}

echo "<a href='browse.php'>Back to Browse Skills</a>";
$conn->close();
?>