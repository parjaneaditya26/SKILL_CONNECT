<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = "Security check failed. Please try again.";
  } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $conn->query("SELECT * FROM users WHERE email = '" . $conn->real_escape_string($email) . "'");
    if ($result && $result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['profileName'] = $user['name'];
        header("Location: index.php");
        exit;
      } else {
        $error = "Incorrect password.";
      }
    } else {
      $error = "No profile found with that email.";
    }
  }
}

layoutStart("Log In", "Student Log In", "Welcome back to Skill Connect.");
?>
    <?php if ($error) { ?>
      <p style="color:var(--danger); text-align:center; margin-bottom:16px; font-weight:600;"><?php echo h($error); ?></p>
    <?php } ?>
    <form action="login.php" method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="you@mit.asia">
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
    <p style="text-align:center; margin-top:16px; font-size:0.9rem; color:var(--text-muted);">
      <a href="forgot_password.php" style="color:var(--accent); font-weight:600;">Forgot password?</a>
    </p>
    <p style="text-align:center; margin-top:8px; font-size:0.9rem; color:var(--text-muted);">
      New here? <a href="profile.php" style="color:var(--accent); font-weight:600;">Create a profile</a>
      &nbsp;·&nbsp;
      <a href="admin_login.php" style="color:var(--accent-dark); font-weight:600;">🛡️ Admin Access</a>
    </p>
<?php layoutEnd(); ?>