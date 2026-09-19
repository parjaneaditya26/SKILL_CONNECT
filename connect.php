<?php
include 'db_connect.php';

$requester = $_POST['requester_name'];
$target_id = $_POST['target_id'];
$target_name = $_POST['target_name'];

// Check if this requester already sent a request to this target
$check = $conn->query("SELECT id FROM requests WHERE requester_name = '$requester' AND target_id = $target_id");

if ($check->num_rows > 0) {
  echo "<p>You've already sent a request to " . $target_name . ".</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
} else {
  $sql = "INSERT INTO requests (requester_name, target_id, target_name) VALUES ('$requester', '$target_id', '$target_name')";
  if ($conn->query($sql) === TRUE) {
    echo "<p>Connection request sent to " . $target_name . "!</p>";
    echo "<a href='browse.php'>Back to Browse Skills</a>";
  } else {
    echo "Error: " . $conn->error;
  }
}

$conn->close();
?>