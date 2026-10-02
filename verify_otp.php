<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$email = isset($_GET['email']) ? $_GET['email'] : (isset($_POST['email']) ? $_POST['email'] : "");
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = "Security check failed. Please try again.";
  } else {
    $e = $conn->real_escape_string(trim($_POST['email']));
    $otp = trim($_POST['otp']);
    $o = $conn->real_escape_string($otp);

    $result = $conn->query("SELECT * FROM users WHERE email = '$e' AND verify_otp = '$o' AND verify_otp_expires > NOW()");

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      $conn->query("UPDATE users SET verified = 1, verify_otp = NULL, verify_otp_expires = NULL WHERE id = {$user['id']}");
      $success = true;
    } else {
      $error = "Invalid or expired code. Please try again or resend a new code.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Your Email - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo isset($_SESSION['profileName']) ? htmlspecialchars($_SESSION['profileName']) : ''; ?>">
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <?php if (isset($_SESSION['profileName'])) { ?>
        <a href="profile.php" class="nav-link">My Profile</a>
        <a href="browse.php" class="nav-link">Browse Skills</a>
      <?php } else { ?>
        <a href="login.php" class="nav-link">Login</a>
      <?php } ?>
    </div>
  </nav>
  <header>
    <h1>Verify Your Email</h1>
    <p>Enter the 6-digit code we sent you.</p>
  </header>
  <main>
    <?php if ($success) { ?>
      <p style="color:#0d9488; text-align:center; margin-bottom:16px; font-weight:600;">✓ Email verified successfully!</p>
      <p style="text-align:center;"><a href="<?php echo isset($_SESSION['profileName']) ? 'profile.php' : 'login.php'; ?>" class="connectBtn" style="display:inline-block; text-decoration:none;">Continue</a></p>
    <?php } else { ?>
      <?php if ($error) { ?>
        <p style="color:#dc2626; text-align:center; margin-bottom:16px; font-weight:600;"><?php echo $error; ?></p>
      <?php } ?>
      <form action="verify_otp.php" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
          <label for="otp">6-Digit Code</label>
          <input type="text" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required>
        </div>
        <button type="submit">Verify</button>
      </form>
      <p style="text-align:center; margin-top:16px; font-size:0.9rem;">
        <a href="resend_verification.php" style="color:#0d9488; font-weight:600;">Resend code</a>
      </p>
    <?php } ?>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>