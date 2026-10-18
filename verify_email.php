<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$token = isset($_GET['token']) ? $_GET['token'] : "";
$t = $conn->real_escape_string($token);

$result = $conn->query("SELECT id, name FROM users WHERE verify_token = '$t'");
$success = false;
$name = "";

if ($result && $result->num_rows > 0) {
  $user = $result->fetch_assoc();
  $conn->query("UPDATE users SET verified = 1, verify_token = NULL WHERE id = {$user['id']}");
  $success = true;
  $name = $user['name'];
}

layoutStart("Verify Email", "Email Verification", "Confirming your campus email address.");
?>
    <?php if ($success) { ?>
      <div class="empty-state">
        <h3 style="color:var(--accent);">✓ Email verified successfully, <?php echo htmlspecialchars($name); ?>!</h3>
        <p style="margin-top:8px;"><a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none;">Go to Login</a></p>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3 style="color:var(--danger);">Verification Link Invalid</h3>
        <p>This verification link is invalid or has already been used.</p>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>