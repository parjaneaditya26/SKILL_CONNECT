<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

$loggedIn = isset($_SESSION['profileName']);
$user = null;

if ($loggedIn) {
  $name = $_SESSION['profileName'];
  $result = $conn->query("SELECT * FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
  if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
  } else {
    session_destroy();
    $loggedIn = false;
  }
}

$branches = ["Computer Engineering","Information Technology","Mechanical Engineering","Civil Engineering","Electronics & Telecommunication","Electrical Engineering","Other"];
$years = ["FY","SY","TY","Final Year"];
$savedBanner = isset($_GET['saved']) ? "Profile saved! Your skills and details are up to date." : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo h(csrf_token()); ?>">
  <title><?php echo $loggedIn ? "Edit My Profile" : "Create Your Profile"; ?> - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo $loggedIn ? htmlspecialchars($user['name']) : ''; ?>">
  <?php siteNav($loggedIn ? $user['name'] : ''); ?>

  <header>
    <h1><?php echo $loggedIn ? "Edit My Profile" : "Create Your Profile"; ?></h1>
    <p><?php echo $loggedIn ? "Update your skills or contact details." : "Verify your email with OTP and tell classmates what you can teach and want to learn."; ?></p>
  </header>

  <?php if ($loggedIn && $savedBanner) { ?>
    <div class="verify-wrap">
      <div class="notice" style="background:#e6f0e2; border-color:#b7d9ad; color:#2b5423; margin-bottom:14px;">✓ <?php echo h($savedBanner); ?></div>
    </div>
  <?php } ?>

  <main>
    <form id="profileForm" action="save_profile.php" method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="name">Your Name</label>
        <input type="text" id="name" name="name"
          value="<?php echo $loggedIn ? htmlspecialchars($user['name']) : ''; ?>"
          placeholder="e.g. Priya Sharma"
          <?php echo $loggedIn ? "readonly" : "required"; ?>>
      </div>

      <div class="form-group">
        <label for="email" style="display:flex; justify-content:space-between; align-items:center;">
          <span>Email Address</span>
          <?php if ($loggedIn && !empty($user['verified'])) { ?>
            <span style="color:#166534; font-weight:700; font-size:0.82rem;">✓ Email Verified</span>
          <?php } ?>
        </label>
        <?php if (!$loggedIn) { ?>
          <div style="display:flex; gap:8px; align-items:stretch;">
            <input type="email" id="email" name="email" placeholder="e.g. you@gmail.com" required style="flex:1; margin-bottom:0;">
            <button type="button" id="sendSignupOtpBtn" class="btn-outline" style="white-space:nowrap; padding:10px 16px; font-weight:600; border-color:var(--accent); color:var(--accent-dark);">📩 Send OTP</button>
          </div>
        <?php } else { ?>
          <input type="email" id="email" name="email"
            value="<?php echo htmlspecialchars($user['email']); ?>"
            placeholder="e.g. you@gmail.com" readonly>
        <?php } ?>
      </div>

      <?php if (!$loggedIn) { ?>
        <div class="form-group" id="signupOtpBox" style="background:var(--bg-soft); border:1px solid var(--border); border-radius:12px; padding:14px 16px; margin-bottom:18px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <label for="signup_otp" style="margin:0; font-size:0.88rem;">🔑 Verify Email by OTP (6-Digit Code)</label>
            <span id="signupOtpStatusBadge" style="font-size:0.78rem; font-weight:700; padding:3px 10px; border-radius:999px; background:#fef3c7; color:#92400e;">Step 1: Send &amp; Verify OTP</span>
          </div>
          <div style="display:flex; gap:8px; align-items:stretch;">
            <input type="text" id="signup_otp" name="signup_otp" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6-digit OTP" required style="flex:1; margin-bottom:0; letter-spacing:4px; font-weight:700; font-size:1rem;">
            <button type="button" id="verifySignupOtpBtn" class="connectBtn" style="white-space:nowrap; padding:10px 18px; margin:0;">✓ Verify OTP</button>
          </div>
          <p id="signupOtpHelperText" style="margin:8px 0 0; font-size:0.82rem; color:var(--text-muted);">
            Enter your email above and click <strong>📩 Send OTP</strong>, then enter the 6-digit code to verify your email.
          </p>
        </div>
      <?php } ?>

      <div class="form-group">
        <label for="mobile">Mobile Number</label>
        <input type="tel" id="mobile" name="mobile"
          value="<?php echo $loggedIn ? htmlspecialchars($user['mobile']) : ''; ?>"
          placeholder="e.g. 9876543210" pattern="[0-9]{10}" required>
      </div>
      <div class="form-group">
        <label for="password"><?php echo $loggedIn ? "New Password (leave blank to keep current)" : "Choose a Password"; ?></label>
        <div class="password-wrap">
          <input type="password" id="password" name="password"
            placeholder="<?php echo $loggedIn ? 'Leave blank to keep current' : 'Choose a password'; ?>"
            minlength="6"
            <?php echo $loggedIn ? "" : "required"; ?>>
          <button type="button" class="toggle-password" data-target="password">👁</button>
        </div>
      </div>
      <div class="form-group">
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
          <option value="">-- Select your branch --</option>
          <?php foreach ($branches as $b) {
            $sel = ($loggedIn && $user['branch'] == $b) ? "selected" : "";
            echo '<option value="' . htmlspecialchars($b) . '" ' . $sel . '>' . htmlspecialchars($b) . '</option>';
          } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="year_of_study">Year of Study</label>
        <select id="year_of_study" name="year_of_study" required>
          <option value="">-- Select your year --</option>
          <?php foreach ($years as $y) {
            $sel = ($loggedIn && $user['year_of_study'] == $y) ? "selected" : "";
            echo '<option value="' . htmlspecialchars($y) . '" ' . $sel . '>' . htmlspecialchars($y) . '</option>';
          } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="teachSkill">Skills You Can Teach</label>
        <input type="text" id="teachSkill" name="teachSkill"
          value="<?php echo ($loggedIn && $user['teach_skill'] != 'Nothing yet') ? htmlspecialchars($user['teach_skill']) : ''; ?>"
          placeholder="e.g. Python, React, DSA (comma-separated, optional)">
      </div>
      <div class="form-group">
        <label for="learnSkill">Skills You Want to Learn</label>
        <input type="text" id="learnSkill" name="learnSkill"
          value="<?php echo ($loggedIn && $user['learn_skill'] != 'Nothing yet') ? htmlspecialchars($user['learn_skill']) : ''; ?>"
          placeholder="e.g. Machine Learning, Figma (comma-separated, optional)">
      </div>
      <button type="submit" id="createProfileSubmitBtn"><?php echo $loggedIn ? "Save Changes" : "Create Verified Profile"; ?></button>
    </form>

    <?php if (!$loggedIn) { ?>
      <p style="text-align:center; margin-top:16px; font-size:0.9rem; color:var(--text-muted);">
        Already have a profile? <a href="login.php" style="color:var(--accent); font-weight:600;">Log in</a>
      </p>
    <?php } ?>

    <?php if ($loggedIn) { ?>
      <div class="danger-zone">
        <h3>Delete Your Profile</h3>
        <p>Enter your password to permanently remove your profile. Only you can do this.</p>
        <form action="delete_profile.php" method="POST" class="ajax-form inline-form" onsubmit="return confirm('Are you sure? This cannot be undone.');">
          <?php echo csrf_field(); ?>
          <div class="password-wrap">
            <input type="password" id="deletePassword" name="password" placeholder="Your password" required>
            <button type="button" class="toggle-password" data-target="deletePassword">👁</button>
          </div>
          <button type="submit" class="deleteBtn">Delete Profile</button>
        </form>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
