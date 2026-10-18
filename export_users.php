<?php
session_start();
if (empty($_SESSION["isAdmin"])) {
  header("Location: login.php");
  exit;
}

include 'db_connect.php';
include 'helpers.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="skill_connect_users.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Email', 'Mobile', 'Branch', 'Year', 'Teaches', 'Wants to Learn', 'Verified', 'Joined']);

$result = $conn->query("SELECT * FROM users ORDER BY id");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
      $row['id'],
      $row['name'],
      $row['email'],
      $row['mobile'],
      $row['branch'] ?? '',
      $row['year_of_study'] ?? '',
      $row['teach_skill'],
      $row['learn_skill'],
      !empty($row['verified']) ? 'Yes' : 'No',
      $row['created_at'] ?? ''
    ]);
  }
}

fclose($output);
exit;
?>