<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  echo "<p>You must be logged in to rate someone.</p>";
  echo "<a href='login.php'>Log In</a>";
  exit;
}

$rater = $_SESSION['profileName'];
$target_id = intval($_POST['target_id']);
$target_name = $_POST['target_name'];
$rating = intval($_POST['rating']);
$comment = $conn->real_escape_string($_POST['comment']);

if ($rater == $target_name) {
  echo "<p>You can't rate yourself!</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
  exit;
}

$r = $conn->real_escape_string($rater);
$tn = $conn->real_escape_string($target_name);

$connectionCheck = $conn->query("SELECT id FROM requests WHERE status = 'accepted' AND ((requester_name = '$r' AND target_id = $target_id) OR (requester_name = '$tn' AND target_name = '$r'))");
if ($connectionCheck->num_rows == 0) {
  echo "<p>You can only rate someone you've connected with.</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
  exit;
}

$existing = $conn->query("SELECT id FROM ratings WHERE rater_name = '$r' AND target_id = $target_id");

if ($existing->num_rows > 0) {
  $row = $existing->fetch_assoc();
  $sql = "UPDATE ratings SET rating = $rating, comment = '$comment', created_at = NOW() WHERE id = {$row['id']}";
  $message = "Your rating has been updated!";
} else {
  $sql = "INSERT INTO ratings (rater_name, target_id, target_name, rating, comment) VALUES ('$r', $target_id, '$tn', $rating, '$comment')";
  $message = "Thanks for your feedback!";
}

if ($conn->query($sql) === TRUE) {
  echo "<p>$message</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>