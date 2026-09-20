<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  echo "<p>You must be logged in to connect with others.</p>";
  echo "<a href='login.php'>Log In</a>";
  exit;
}

$requester = $_SESSION['profileName'];
$target_id = intval($_POST['target_id']);
$target_name = $_POST['target_name'];

if ($requester == $target_name) {
  echo "<p>You can't connect with yourself!</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
  exit;
}

$req = $conn->real_escape_string($requester);
$check = $conn->query("SELECT id FROM requests WHERE requester_name = '$req' AND target_id = $target_id");

if ($check->num_rows > 0) {
  echo "<p>You've already sent a request to " . htmlspecialchars($target_name) . ".</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
} else {
  $tn = $conn->real_escape_string($target_name);
  $sql = "INSERT INTO requests (requester_name, target_id, target_name) VALUES ('$req', $target_id, '$tn')";
  if ($conn->query($sql) === TRUE) {
    echo "<p>Connection request sent to " . htmlspecialchars($target_name) . "!</p>";
    echo "<a href='browse.php'>Back to Browse Skills</a>";
  } else {
    echo "Error: " . $conn->error;
  }
}

$conn->close();
?>