<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = "Security check failed. Please try again.";
  } else {
    $name = trim($_POST['name']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['profileName'] = $name;
        header("Location: browse.php");
        exit;
      } else {
        $error = "Incorrect password.";
      }
    } else {
      $error = "No profile found with that name.";
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
    <a href="index.php">Home</a>
    <a href="profile.php">Create Profile</a>
    <a href="login.php">Login</a>
  </nav>
  <header>
    <h1>Log In</h1>
    <p>Welcome back to Skill Connect.</p>
  </header>
  <main>
    <?php if ($error) { ?>
      <p style="color:#c1272d; text-align:center; margin-bottom:16px; font-weight:600;"><?php echo $error; ?></p>
    <?php } ?>
    <form action="login.php" method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="name">Your Name</label>
        <input type="text" id="name" name="name" required>
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
    <p style="text-align:center; margin-top:16px; font-size:0.9rem; color:#666;">
      <a href="forgot_password.php" style="color:#1d4ed8; font-weight:600;">Forgot password?</a>
    </p>
    <p style="text-align:center; margin-top:8px; font-size:0.9rem; color:#666;">
      New here? <a href="profile.php" style="color:#1d4ed8; font-weight:600;">Create a profile</a>
    </p>
  </main>
  <script src="script.js"></script>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
</body>
</html>