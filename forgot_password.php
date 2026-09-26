<?php
session_start();
include 'db_connect.php';
@include 'mailer.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($_POST['email']);
  $e = $conn->real_escape_string($email);
  $result = $conn->query("SELECT * FROM users WHERE email = '$e'");
  if ($result->num_rows > 0 && function_exists('sendEmail')) {
    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
    $t = $conn->real_escape_string($token);
    $conn->query("UPDATE users SET reset_token='$t', reset_expires='$expires' WHERE id={$user['id']}");

    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
    sendEmail(
      $email,
      $user['name'],
      "Reset Your Skill Connect Password",
      "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Click below to reset your password. This link expires in 1 hour.</p><p><a href='$resetLink'>Reset Password</a></p>"
    );
  }
  $message = "If that email is registered, a reset link has been sent to it.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="">
  <nav>
    <a href="index.php">Home</a>
    <a href="profile.php">Create Profile</a>
    <a href="login.php">Login</a>
  </nav>
  <header>
    <h1>Forgot Password</h1>
    <p>Enter your email and we'll send you a reset link.</p>
  </header>
  <main>
    <?php if ($message) { ?>
      <p style="text-align:center; margin-bottom:16px; font-weight:600; color:#1d4ed8;"><?php echo $message; ?></p>
    <?php } ?>
    <form action="forgot_password.php" method="POST">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required>
      </div>
      <button type="submit">Send Reset Link</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:0.9rem;">
      <a href="login.php" style="color:#1d4ed8; font-weight:600;">Back to Login</a>
    </p>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
</body>
</html>