<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="skill_connect_users.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Email', 'Mobile', 'Teaches', 'Wants to Learn', 'Category', 'Verified', 'Joined']);

$result = $conn->query("SELECT * FROM users ORDER BY id");
while ($row = $result->fetch_assoc()) {
  fputcsv($output, [$row['id'], $row['name'], $row['email'], $row['mobile'], $row['teach_skill'], $row['learn_skill'], $row['category'], $row['verified'] ? 'Yes' : 'No', $row['created_at']]);
}

fclose($output);
exit;
?>