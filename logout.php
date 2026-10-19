<?php
require_once __DIR__ . '/session_init.php';
session_destroy();
header("Location: index.php");
exit;
?>