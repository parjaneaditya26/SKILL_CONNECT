<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  echo "<p>You must be logged in to delete a profile.</p>";
  echo "<a href='login.php'>Log In</a>";
  exit;
}

$name = $_SESSION['profileName'];
$password = $_POST['password'];

$result = $conn->query("SELECT password FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
  $conn->query("DELETE FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
  session_destroy();
  echo "<p>Your profile has been deleted.</p>";
  echo "<a href='index.php'>Back to Home</a>";
} else {
  echo "<p>Incorrect password. Profile not deleted.</p>";
  echo "<a href='profile.php'>Back to Profile</a>";
}

$conn->close();
?>