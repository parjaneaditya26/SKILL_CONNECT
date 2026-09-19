<?php
include 'db_connect.php';

header('Content-Type: application/json');

$name = isset($_GET['name']) ? $_GET['name'] : "";
$count = 0;

if ($name != "") {
  $result = $conn->query("SELECT COUNT(*) as total FROM requests WHERE target_name = '$name' AND status = 'pending'");
  $row = $result->fetch_assoc();
  $count = $row['total'];
}

echo json_encode(["count" => $count]);
?>