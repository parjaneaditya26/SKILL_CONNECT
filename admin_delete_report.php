<?php
session_start();
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required. Please log in with admin password."]);
  exit;
}

include 'db_connect.php';

$id = intval($_POST['id'] ?? 0);
$conn->query("DELETE FROM reports WHERE id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>