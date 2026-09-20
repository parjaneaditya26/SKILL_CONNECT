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
} else {
  $r = $conn->real_escape_string($rater);
  $tn = $conn->real_escape_string($target_name);
  $sql = "INSERT INTO ratings (rater_name, target_id, target_name, rating, comment) VALUES ('$r', $target_id, '$tn', $rating, '$comment')";
  if ($conn->query($sql) === TRUE) {
    echo "<p>Thanks for your feedback!</p>";
    echo "<a href='browse.php'>Back to Browse Skills</a>";
  } else {
    echo "Error: " . $conn->error;
  }
}

$conn->close();
?>