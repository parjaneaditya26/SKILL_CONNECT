<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = "Security check failed. Please try again.";
  } else {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '" . $conn->real_escape_string($email) . "'");
    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['profileName'] = $user['name'];
        header("Location: browse.php");
        exit;
      } else {
        $error = "Incorrect password.";
      }
    } else {
      $error = "No profile found with that email.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log In - Skill Connect</title>
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
      <a href="profile.php" class="nav-link">Create Profile</a>
      <a href="login.php" class="nav-link">Login</a>
    </div>
  </nav>
  <header>
    <h1>Log In</h1>
    <p>Welcome back to Skill Connect.</p>
  </header>
  <main>
    <?php if ($error) { ?>
      <p style="color:#dc2626; text-align:center; margin-bottom:16px; font-weight:600;"><?php echo $error; ?></p>
    <?php } ?>
    <form action="login.php" method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" required>
          <button type="button" class="toggle-password" data-target="password">👁</button>
        </div>
      </div>
      <button type="submit">Log In</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:0.9rem; color:#64748b;">
      <a href="forgot_password.php" style="color:#0d9488; font-weight:600;">Forgot password?</a>
    </p>
    <p style="text-align:center; margin-top:8px; font-size:0.9rem; color:#64748b;">
      New here? <a href="profile.php" style="color:#0d9488; font-weight:600;">Create a profile</a>
    </p>
  </main>
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
  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
  </footer>
</body>
</html>