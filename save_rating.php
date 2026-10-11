<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "You must be logged in to rate someone."]);
  exit;
}

$rater = $_SESSION['profileName'];
$target_id = intval($_POST['target_id']);
$target_name = $_POST['target_name'];
$rating = intval($_POST['rating']);
$comment = $conn->real_escape_string($_POST['comment']);

if ($rater == $target_name) {
  echo json_encode(["success" => false, "message" => "You can't rate yourself!"]);
  exit;
}

$r = $conn->real_escape_string($rater);
$tn = $conn->real_escape_string($target_name);

$connectionCheck = $conn->query("SELECT id FROM requests WHERE status = 'accepted' AND ((requester_name = '$r' AND target_id = $target_id) OR (requester_name = '$tn' AND target_name = '$r'))");
if ($connectionCheck->num_rows == 0) {
  echo json_encode(["success" => false, "message" => "You can only rate someone you've connected with."]);
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
  echo json_encode(["success" => true, "message" => $message]);
} else {
  echo json_encode(["success" => false, "message" => $conn->error]);
}
$conn->close();
?>
