<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : "");
$error = "";
$success = false;

$t = $conn->real_escape_string($token);
$result = $conn->query("SELECT * FROM users WHERE reset_token = '$t' AND reset_expires > NOW()");

if ($t == "" || $result->num_rows == 0) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="">
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <a href="login.php" class="nav-link">Login</a>
    </div>
  </nav>
  <header>
    <h1>Reset Password</h1>
    <p>Choose a new password.</p>
  </header>
  <main>
    <?php if ($error) { ?>
      <p style="color:#dc2626; text-align:center; margin-bottom:16px; font-weight:600;"><?php echo $error; ?></p>
    <?php } elseif ($success) { ?>
      <p style="color:#0d9488; text-align:center; margin-bottom:16px; font-weight:600;">Password updated! You can now log in.</p>
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
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
  </footer>
  <div id="chatbot-window">
    <div id="chatbot-header">💬 Skill Connect Assistant</div>
    <div id="chatbot-messages">
      <div class="chat-msg bot">Hi! I'm the Skill Connect assistant. Ask me how to use the site, or for skill-learning advice.</div>
    </div>
    <div id="chatbot-input-row">
      <input type="text" id="chatbot-input" placeholder="Ask a question...">
      <button id="chatbot-send" type="button">Send</button>
    </div>
  </div>
  <button id="chatbot-toggle">💬</button>
  <script src="script.js"></script>
</body>
</html>