<?php
require_once __DIR__ . '/session_init.php';
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required. Please log in with admin password."]);
  exit;
}

include 'db_connect.php';
include 'helpers.php';

if (!csrf_verify_request()) {
  http_response_code(403);
  die("Security check failed. Go back, refresh the admin page and try again.");
}

$id = intval($_POST['id'] ?? 0);

$nameStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if ($nameStmt) {
  $nameStmt->bind_param("i", $id);
  $nameStmt->execute();
  $r = $nameStmt->get_result();
  $nameRow = $r ? $r->fetch_assoc() : null;
  $nameStmt->close();
  if ($nameRow) { cleanupUserData($conn, $nameRow['name']); }
}

$conn->query("DELETE FROM users WHERE id = $id");
$conn->query("DELETE FROM requests WHERE target_id = $id");
$conn->query("DELETE FROM ratings WHERE target_id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>