<?php
session_start();
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required."]);
  exit;
}

include 'db_connect.php';
include 'helpers.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$defaultPass = password_hash("student123", PASSWORD_DEFAULT);

$sampleUsers = [
  ['Aarav Deshmukh', '9823011223', 'aarav.deshmukh@mit.asia', 'Computer Engineering', 'TY', 'React, JavaScript, PHP, Git', 'Machine Learning, Cloud AWS', 1],
  ['Sneha Kulkarni', '9823044556', 'sneha.kulkarni@mit.asia', 'Information Technology', 'Final Year', 'Data Structures, C++, Java, SQL', 'UI/UX Design, Flutter', 1],
  ['Rohan Patil', '9823077889', 'rohan.patil@mit.asia', 'Computer Engineering', 'TY', 'Python, Machine Learning, Data Science', 'React, System Design', 1],
  ['Priya Sharma', '9823099001', 'priya.sharma@mit.asia', 'Information Technology', 'SY', 'UI/UX Design, Figma, HTML/CSS', 'Python, JavaScript', 1],
  ['Aditya Joshi', '9823022334', 'aditya.joshi@mit.asia', 'Electronics & Telecommunication', 'TY', 'Embedded C, Arduino, IoT, PCB Design', 'Python, Cloud AWS', 1],
  ['Ananya More', '9823055667', 'ananya.more@mit.asia', 'Mechanical Engineering', 'Final Year', 'AutoCAD, SolidWorks, MATLAB', 'Python, Data Analysis', 1]
];

$addedUsers = 0;
foreach ($sampleUsers as $su) {
  $nameEsc = $conn->real_escape_string($su[0]);
  $chk = $conn->query("SELECT id FROM users WHERE name = '$nameEsc'");
  if ($chk && $chk->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (name, mobile, email, password, branch, year_of_study, teach_skill, learn_skill, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
      $stmt->bind_param("ssssssssi", $su[0], $su[1], $su[2], $defaultPass, $su[3], $su[4], $su[5], $su[6], $su[7]);
      if ($stmt->execute()) { $addedUsers++; }
      $stmt->close();
    }
  }
}

$chkReq = $conn->query("SELECT COUNT(*) AS c FROM requests");
if ($chkReq && intval($chkReq->fetch_assoc()['c']) < 3) {
  $uRes = $conn->query("SELECT id, name FROM users LIMIT 4");
  $uList = $uRes ? $uRes->fetch_all(MYSQLI_ASSOC) : [];
  if (count($uList) >= 3) {
    $conn->query("INSERT INTO requests (requester_name, target_id, target_name, status) VALUES
      ('" . $conn->real_escape_string($uList[0]['name']) . "', " . intval($uList[1]['id']) . ", '" . $conn->real_escape_string($uList[1]['name']) . "', 'accepted'),
      ('" . $conn->real_escape_string($uList[2]['name']) . "', " . intval($uList[0]['id']) . ", '" . $conn->real_escape_string($uList[0]['name']) . "', 'accepted'),
      ('" . $conn->real_escape_string($uList[1]['name']) . "', " . intval($uList[2]['id']) . ", '" . $conn->real_escape_string($uList[2]['name']) . "', 'pending')
    ");
  }
}

$chkHelp = $conn->query("SELECT COUNT(*) AS c FROM help_posts");
if ($chkHelp && intval($chkHelp->fetch_assoc()['c']) < 2) {
  $conn->query("INSERT INTO help_posts (author_name, type, title, details, status) VALUES
    ('Aarav Deshmukh', 'teammates', 'Need 2 teammates for Smart India Hackathon (Frontend + ML)', 'We are building an AI crop disease detector for SIH 2026. Looking for a Python/ML developer and a UI designer.', 'open'),
    ('Priya Sharma', 'help', 'Need help debugging PHP MySQL connection in XAMPP', 'Getting Access Denied error on port 3307 when connecting from db_connect.php. Can someone guide me for 15 mins?', 'open'),
    ('Aditya Joshi', 'teammates', 'Looking for Android/Flutter dev for IoT Smart Campus project', 'Hardware node with ESP32 is ready. Need a partner to build a mobile dashboard app.', 'open')
  ");
}

echo json_encode(["success" => true, "message" => "Sample campus data loaded ($addedUsers new student profiles added)!"]);
$conn->close();
?>
