<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : '';
$isAdmin = !empty($_SESSION['isAdmin']);

if ($currentUser === '' && !$isAdmin) {
  echo json_encode(["success" => false, "message" => "Login required."]);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'delete_all' && $isAdmin) {
  $conn->query("DELETE FROM peer_session_participants");
  $conn->query("DELETE FROM peer_sessions");
  echo json_encode(["success" => true, "message" => "All peer teaching sessions deleted permanently."]);
  $conn->close();
  exit;
}

$session = null;
$s = $conn->prepare("SELECT id, host_name, featured FROM peer_sessions WHERE id = ?");
if ($s) {
  $s->bind_param("i", $id);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $session = $r->fetch_assoc(); }
  $s->close();
}

if (!$session) {
  echo json_encode(["success" => false, "message" => "Session not found."]);
  exit;
}

if (!$isAdmin && $session['host_name'] !== $currentUser) {
  echo json_encode(["success" => false, "message" => "Only the host or admin can modify this session."]);
  exit;
}

if ($action === 'delete') {
  $d1 = $conn->prepare("DELETE FROM peer_session_participants WHERE session_id = ?");
  if ($d1) { $d1->bind_param("i", $id); $d1->execute(); $d1->close(); }
  $d2 = $conn->prepare("DELETE FROM peer_sessions WHERE id = ?");
  if ($d2) { $d2->bind_param("i", $id); $d2->execute(); $d2->close(); }
  echo json_encode(["success" => true, "message" => "Peer session deleted."]);
} elseif ($action === 'complete' || $action === 'reopen') {
  $newStatus = ($action === 'complete') ? 'completed' : 'upcoming';
  $u = $conn->prepare("UPDATE peer_sessions SET status = ? WHERE id = ?");
  if ($u) { $u->bind_param("si", $newStatus, $id); $u->execute(); $u->close(); }
  echo json_encode(["success" => true, "message" => $newStatus === 'completed' ? "Session marked as completed!" : "Session reopened!"]);
} elseif ($action === 'feature') {
  $newFeat = $session['featured'] ? 0 : 1;
  $u = $conn->prepare("UPDATE peer_sessions SET featured = ? WHERE id = ?");
  if ($u) { $u->bind_param("ii", $newFeat, $id); $u->execute(); $u->close(); }
  echo json_encode(["success" => true, "message" => $newFeat ? "Session featured!" : "Removed featured badge."]);
} else {
  echo json_encode(["success" => false, "message" => "Unknown action."]);
}
$conn->close();
?>
