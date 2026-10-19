<?php
require_once __DIR__ . '/session_init.php';
unset($_SESSION['isAdmin']);
header("Location: index.php");
exit;
?>
