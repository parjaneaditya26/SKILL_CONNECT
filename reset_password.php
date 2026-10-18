<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : "");
$error = "";
$success = false;

$t = $conn->real_escape_string($token);
$result = $conn->query("SELECT * FROM users WHERE reset_token = '$t' AND reset_expires > NOW()");

if ($t == "" || !$result || $result->num_rows == 0) {
  $error = "This reset link is invalid or has expired.";
} else {
  $user = $result->fetch_assoc();
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
      $error = "Security check failed. Please try again.";
    } else {
      $newPass = $_POST['new_password'];
      if (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
      } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed', reset_token=NULL, reset_expires=NULL WHERE id={$user['id']}");
        $success = true;
      }
    }
  }
}

layoutStart("Reset Password", "Reset Password", "Choose a new password.");
?>
    <?php if ($error) { ?>
      <p style="color:var(--danger); text-align:center; margin-bottom:16px; font-weight:600;"><?php echo h($error); ?></p>
    <?php } elseif ($success) { ?>
      <p style="color:var(--accent); text-align:center; margin-bottom:16px; font-weight:600;">Password updated! You can now log in.</p>
      <p style="text-align:center;"><a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none;">Go to Login</a></p>
    <?php } else { ?>
      <form action="reset_password.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
          <label for="new_password">New Password</label>
          <div class="password-wrap">
            <input type="password" id="new_password" name="new_password" minlength="6" required>
            <button type="button" class="toggle-password" data-target="new_password">👁</button>
          </div>
        </div>
        <button type="submit">Update Password</button>
      </form>
    <?php } ?>
<?php layoutEnd(); ?>