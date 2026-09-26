<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';

$id = intval($_POST['id']);

$conn->query("DELETE FROM requests WHERE id = $id");

$conn->close();
header("Location: admin.php");
exit;
?>