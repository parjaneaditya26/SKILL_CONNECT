<?php
session_start();
unset($_SESSION['isAdmin']);
header("Location: admin_login.php");
exit;
?>