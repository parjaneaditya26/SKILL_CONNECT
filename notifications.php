<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';

header('Content-Type: application/json');

// Only ever report the pending-request count of the logged-in user (ignores any ?name= in the URL).
$name = $_SESSION['profileName'] ?? "";
$count = 0;

if ($name !== "") {
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM requests WHERE target_name = ? AND status = 'pending'");
  if ($stmt) {
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $count = $row ? intval($row['total']) : 0;
    $stmt->close();
  }
}

echo json_encode(["count" => $count]);
?>
