<?php
include 'db_connect.php';

$token = isset($_GET['token']) ? $_GET['token'] : "";
$t = $conn->real_escape_string($token);

$result = $conn->query("SELECT id, name FROM users WHERE verify_token = '$t'");
$success = false;
$name = "";

if ($result->num_rows > 0) {
  $user = $result->fetch_assoc();
  $conn->query("UPDATE users SET verified = 1, verify_token = NULL WHERE id = {$user['id']}");
  $success = true;
  $name = $user['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Email - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="">
  <nav><a href="index.php">Home</a></nav>
  <header>
    <h1>Email Verification</h1>
  </header>
  <main>
    <?php if ($success) { ?>
      <p style="text-align:center; color:#1d4ed8; font-weight:600;">✓ Email verified successfully, <?php echo htmlspecialchars($name); ?>!</p>
      <p style="text-align:center; margin-top:10px;"><a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none;">Go to Login</a></p>
    <?php } else { ?>
      <p style="text-align:center; color:#c1272d; font-weight:600;">This verification link is invalid or already used.</p>
    <?php } ?>
  </main>
  <footer><p>Skill Connect &copy; 2026 — A College Project (CEP)</p></footer>
</body>
</html>