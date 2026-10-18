<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$error = "";
$ADMIN_USERNAME = "admin";
$ADMIN_PASSWORD = "skillconnect2026";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = "Security check failed. Please try again.";
  } else {
    $loginInput = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // If admin logs in via Student Login with username "admin" and password "skillconnect2026", open Admin Dashboard
    if (strtolower($loginInput) === strtolower($ADMIN_USERNAME) && $password === $ADMIN_PASSWORD) {
      $_SESSION['isAdmin'] = true;
      header("Location: admin.php");
      exit;
    }

    $esc = $conn->real_escape_string($loginInput);
    $result = $conn->query("SELECT * FROM users WHERE email = '$esc' OR name = '$esc' LIMIT 1");
    if ($result && $result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($_POST['password'] ?? '', $user['password'])) {
        $_SESSION['profileName'] = $user['name'];
        header("Location: index.php");
        exit;
      } else {
        $error = "Incorrect password.";
      }
    } else {
      $error = "No profile found with that email or username.";
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
        <label for="email">Email Address / Username</label>
        <input type="text" id="email" name="email" required placeholder="you@mit.asia">
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
    </p>
<?php layoutEnd(); ?>
