<?php
session_start();
$error = "";

$ADMIN_USERNAME = "admin";
$ADMIN_PASSWORD = "skillconnect2026"; // CHANGE THIS to your own secret password

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($_POST['username'] == $ADMIN_USERNAME && $_POST['password'] == $ADMIN_PASSWORD) {
    $_SESSION['isAdmin'] = true;
    header("Location: admin.php");
    exit;
  } else {
    $error = "Invalid admin credentials.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="">
  <nav><a href="index.php">Home</a></nav>
  <header>
    <h1>Admin Login</h1>
    <p>Restricted area.</p>
  </header>
  <main>
    <?php if ($error) { ?>
      <p style="color:#c1272d; text-align:center; margin-bottom:16px; font-weight:600;"><?php echo $error; ?></p>
    <?php } ?>
    <form action="admin_login.php" method="POST">
      <div class="form-group">
        <label for="username">Admin Username</label>
        <input type="text" id="username" name="username" required>
      </div>
      <div class="form-group">
        <label for="password">Admin Password</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" required>
          <button type="button" class="toggle-password" data-target="password">👁</button>
        </div>
      </div>
      <button type="submit">Log In</button>
    </form>
  </main>
  <footer><p>Skill Connect &copy; 2026</p></footer>
  <script src="script.js"></script>
</body>
</html>