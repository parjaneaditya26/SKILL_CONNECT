<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!isset($_SESSION['profileName']) && empty($_SESSION['isAdmin'])) {
  echo json_encode(["success" => false, "message" => "Please log in to host a peer teaching session."]);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$hostName = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : 'Campus Admin';
$title = trim($_POST['title'] ?? '');
$skillTopic = trim($_POST['skill_topic'] ?? '');
$description = trim($_POST['description'] ?? '');
$sessionDate = trim($_POST['session_date'] ?? '');
$sessionTime = trim($_POST['session_time'] ?? '');
$durationMins = max(15, min(240, intval($_POST['duration_mins'] ?? 60)));
$mode = ($_POST['mode'] ?? 'Online') === 'Campus' ? 'Campus' : 'Online';
$locationLink = trim($_POST['location_link'] ?? '');
$maxSeats = max(2, min(200, intval($_POST['max_seats'] ?? 20)));

if (textLen($title) < 4 || textLen($title) > 180) {
  echo json_encode(["success" => false, "message" => "Title must be between 4 and 180 characters."]);
  exit;
}
if (textLen($skillTopic) < 2 || textLen($skillTopic) > 100) {
  echo json_encode(["success" => false, "message" => "Please enter a valid skill or topic."]);
  exit;
}
if (textLen($description) < 10) {
  echo json_encode(["success" => false, "message" => "Please add a short description of what students will learn."]);
  exit;
}
if ($sessionDate === '' || $sessionTime === '' || $locationLink === '') {
  echo json_encode(["success" => false, "message" => "Date, time, and location/meeting link are required."]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO peer_sessions (host_name, title, skill_topic, description, session_date, session_time, duration_mins, mode, location_link, max_seats, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
  exit;
}
$stmt->bind_param("ssssssissi", $hostName, $title, $skillTopic, $description, $sessionDate, $sessionTime, $durationMins, $mode, $locationLink, $maxSeats);

$targetRedirect = (!empty($_POST['from_admin'])) ? "admin.php#sessions-section" : "peer_sessions.php";
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
if ($stmt->execute()) {
  $stmt->close();
  if (!$isAjax) {
    $conn->close();
    header("Location: " . $targetRedirect);
    exit;
  }
  echo json_encode(["success" => true, "message" => "Peer teaching session scheduled!", "redirect" => $targetRedirect]);
} else {
  $err = $conn->error;
  $stmt->close();
  if (!$isAjax) {
    $conn->close();
    header("Location: " . $targetRedirect);
    exit;
  }
  echo json_encode(["success" => false, "message" => "Could not save session: " . $err]);
}
$conn->close();
?>
