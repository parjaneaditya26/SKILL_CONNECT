<?php
session_start();
if (!empty($_SESSION["isAdmin"])) {
  header("Location: admin.php");
  exit;
}
header("Location: login.php");
exit;
?>
