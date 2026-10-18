<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!isset($_SESSION['profileName'])) {
  echo json_encode(["success" => false, "message" => "Please log in to reserve a seat."]);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$sessionId = intval($_POST['session_id'] ?? 0);
$user = $_SESSION['profileName'];

$session = null;
$s = $conn->prepare("SELECT id, host_name, max_seats, status FROM peer_sessions WHERE id = ?");
if ($s) {
  $s->bind_param("i", $sessionId);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $session = $r->fetch_assoc(); }
  $s->close();
}

if (!$session) {
  echo json_encode(["success" => false, "message" => "Session not found."]);
  exit;
}

if ($session['host_name'] === $user) {
  echo json_encode(["success" => false, "message" => "You are hosting this session."]);
  exit;
}

$already = false;
$chk = $conn->prepare("SELECT id FROM peer_session_participants WHERE session_id = ? AND user_name = ?");
if ($chk) {
  $chk->bind_param("is", $sessionId, $user);
  $chk->execute();
  $r = $chk->get_result();
  $already = ($r && $r->num_rows > 0);
  $chk->close();
}

if ($already) {
  $del = $conn->prepare("DELETE FROM peer_session_participants WHERE session_id = ? AND user_name = ?");
  if ($del) {
    $del->bind_param("is", $sessionId, $user);
    $del->execute();
    $del->close();
  }
  echo json_encode(["success" => true, "message" => "You left the session."]);
  exit;
}

$currentCount = 0;
$cntS = $conn->prepare("SELECT COUNT(*) AS c FROM peer_session_participants WHERE session_id = ?");
if ($cntS) {
  $cntS->bind_param("i", $sessionId);
  $cntS->execute();
  $r = $cntS->get_result();
  if ($r) { $currentCount = intval($r->fetch_assoc()['c']); }
  $cntS->close();
}

if ($currentCount >= intval($session['max_seats'])) {
  echo json_encode(["success" => false, "message" => "Sorry, this session is already full."]);
  exit;
}

$ins = $conn->prepare("INSERT INTO peer_session_participants (session_id, user_name) VALUES (?, ?)");
if ($ins) {
  $ins->bind_param("is", $sessionId, $user);
  $ins->execute();
  $ins->close();
}

echo json_encode(["success" => true, "message" => "Seat reserved! Meeting link unlocked."]);
$conn->close();
?>
