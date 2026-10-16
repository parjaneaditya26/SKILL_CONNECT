<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';
include 'helpers.php';

$id = intval($_POST['id']);

$nameStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$nameStmt->bind_param("i", $id);
$nameStmt->execute();
$nameRow = $nameStmt->get_result()->fetch_assoc();
$nameStmt->close();
if ($nameRow) { cleanupUserData($conn, $nameRow['name']); }

$conn->query("DELETE FROM users WHERE id = $id");
$conn->query("DELETE FROM requests WHERE target_id = $id");
$conn->query("DELETE FROM ratings WHERE target_id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>