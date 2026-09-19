<?php
include 'db_connect.php';

$rater = $_POST['rater_name'];
$target_id = $_POST['target_id'];
$target_name = $_POST['target_name'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

if (strtolower($rater) == strtolower($target_name)) {
  echo "<p>You can't rate yourself!</p>";
  echo "<a href='browse.php'>Back to Browse Skills</a>";
} else {
  $sql = "INSERT INTO ratings (rater_name, target_id, target_name, rating, comment) VALUES ('$rater', '$target_id', '$target_name', '$rating', '$comment')";
  if ($conn->query($sql) === TRUE) {
    echo "<p>Thanks for your feedback!</p>";
    echo "<a href='browse.php'>Back to Browse Skills</a>";
  } else {
    echo "Error: " . $conn->error;
  }
}

$conn->close();
?>