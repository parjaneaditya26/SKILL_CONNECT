<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "You must be logged in."]);
  exit;
}

$name = $_SESSION['profileName'];
$password = $_POST['password'];

$result = $conn->query("SELECT password FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
  $conn->query("DELETE FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
  session_destroy();
  echo json_encode(["success" => true, "message" => "Profile deleted.", "redirect" => "index.php"]);
} else {
  echo json_encode(["success" => false, "message" => "Incorrect password."]);
}
$conn->close();
?>
