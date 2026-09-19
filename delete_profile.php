<?php
include 'db_connect.php';

$name = $_POST['name'];

$sql = "DELETE FROM users WHERE name = '$name'";

if ($conn->query($sql) === TRUE) {
  echo "<p>Profile for \"$name\" deleted (if it existed).</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>