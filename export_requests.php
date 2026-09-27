<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="skill_connect_requests.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'From', 'To', 'Status', 'Date']);

$result = $conn->query("SELECT * FROM requests ORDER BY id");
while ($row = $result->fetch_assoc()) {
  fputcsv($output, [$row['id'], $row['requester_name'], $row['target_name'], $row['status'], $row['created_at']]);
}

fclose($output);
exit;
?>