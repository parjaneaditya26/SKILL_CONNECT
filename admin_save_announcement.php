<?php
require_once __DIR__ . '/session_init.php';
if (empty($_SESSION["isAdmin"])) {
  echo json_encode(["success" => false, "message" => "Admin authentication required. Please log in with admin password."]);
  exit;
}

include 'db_connect.php';
include 'helpers.php';
@include 'mailer.php';

header('Content-Type: application/json');

ensureSchema($conn);

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  echo json_encode(["success" => false, "message" => "Security check failed."]);
  exit;
}

$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$category = $_POST['category'] ?? '';
$link = trim($_POST['link'] ?? '');
$deadlineIn = trim($_POST['deadline'] ?? '');
$pinned = isset($_POST['pinned']) ? 1 : 0;
$notify = isset($_POST['notify']);

if (textLen($title) < 3 || textLen($title) > 200) {
  echo json_encode(["success" => false, "message" => "Title must be 3 to 200 characters."]);
  exit;
}
if (textLen($body) < 5 || textLen($body) > 5000) {
  echo json_encode(["success" => false, "message" => "Details must be 5 to 5000 characters."]);
  exit;
}
if (!in_array($category, announcementCategories(), true)) {
  echo json_encode(["success" => false, "message" => "Please choose a valid category."]);
  exit;
}

$linkVal = null;
if ($link !== '') {
  if (strlen($link) > 500 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link)) {
    echo json_encode(["success" => false, "message" => "Link must be a full http:// or https:// address."]);
    exit;
  }
  $linkVal = $link;
}

$deadlineVal = null;
if ($deadlineIn !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $deadlineIn);
  if (!$dt || $dt->format('Y-m-d') !== $deadlineIn) {
    echo json_encode(["success" => false, "message" => "Deadline must be a valid date."]);
    exit;
  }
  $deadlineVal = $deadlineIn;
}

$stmt = $conn->prepare("INSERT INTO announcements (title, body, category, link, deadline, pinned) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
  exit;
}
$stmt->bind_param("sssssi", $title, $body, $category, $linkVal, $deadlineVal, $pinned);
if (!$stmt->execute()) {
  echo json_encode(["success" => false, "message" => "Could not save: " . $conn->error]);
  exit;
}
$stmt->close();

$message = "Announcement posted!";

if ($notify) {
  if (function_exists('sendEmailBcc')) {
    set_time_limit(120);
    $emails = [];
    $res = $conn->query("SELECT email FROM users WHERE verified = 1 AND email <> ''");
    if ($res) { while ($r = $res->fetch_assoc()) { $emails[] = $r['email']; } }

    if (count($emails) == 0) {
      $message .= " No verified students to email yet.";
    } else {
      $site = app_base_url() . "/announcements.php";
      $mailBody = "<p><strong>" . h($category) . ":</strong> " . h($title) . "</p>"
        . "<p>" . nl2br(h(textCut($body, 400))) . "</p>"
        . ($deadlineVal ? "<p><strong>Deadline:</strong> " . h($deadlineVal) . "</p>" : "")
        . "<p><a href='" . h($site) . "'>See all announcements on Skill Connect</a></p>";
      $sent = sendEmailBcc($emails, "New announcement: " . $title, $mailBody);
      $message .= $sent > 0 ? " Email sent to $sent students." : " (The email could not be sent.)";
    }
  } else {
    $message .= " (Email is not set up, so no email was sent.)";
  }
}

echo json_encode(["success" => true, "message" => $message]);
$conn->close();
?>
