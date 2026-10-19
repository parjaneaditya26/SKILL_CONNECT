<?php
require_once __DIR__ . '/session_init.php';
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required. Please log in with admin password."]);
  exit;
}

include 'db_connect.php';

if (!csrf_verify_request()) {
  http_response_code(403);
  die("Security check failed. Go back, refresh the admin page and try again.");
}

$id = intval($_POST['id'] ?? 0);
$conn->query("DELETE FROM requests WHERE id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>