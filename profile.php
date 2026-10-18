<?php
session_start();
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
  <title>My Profile - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo $loggedIn ? htmlspecialchars($user['name']) : ''; ?>">
  <?php siteNav($loggedIn ? $user['name'] : ''); ?>

  <header>
    <h1><?php echo $loggedIn ? "Edit My Profile" : "Create Your Profile"; ?></h1>
    <p><?php echo $loggedIn ? "Update your skills or contact details." : "Tell others what you can teach and what you want to learn."; ?></p>
  </header>

  <?php if ($loggedIn) { ?>
    <div class="verify-wrap">
      <?php if ($savedBanner) { ?>
        <div class="notice" style="background:#e6f0e2; border-color:#b7d9ad; color:#2b5423; margin-bottom:14px;">✓ <?php echo h($savedBanner); ?></div>
      <?php } ?>
      <?php if ($user['verified']) { ?>
        <p style="color:var(--accent-dark); font-weight:600; font-size:0.88rem;">✓ Email Verified</p>
      <?php } else { ?>
        <div class="empty-state" style="max-width:none; margin:0; padding:20px; text-align:left;">
          <h3 style="color:#b45309; font-size:1rem;">Email not verified</h3>
          <p>Step 1: we'll send a 6-digit code to <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
          <form action="resend_verification.php" method="POST" class="ajax-form inline-form" data-no-reload="1" style="margin:8px 0 0;">
            <button type="submit" style="width:100%;">Send verification code</button>
          </form>
          <p style="margin-top:16px;">Step 2: enter the code from your email</p>
          <form action="verify_otp_ajax.php" method="POST" class="ajax-form inline-form" style="margin:8px 0 0;">
            <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; margin-bottom:8px; font-size:1rem; letter-spacing:3px;">
            <button type="submit" style="width:100%;">Verify</button>
          </form>
        </div>
      <?php } ?>
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
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
          value="<?php echo $loggedIn ? htmlspecialchars($user['email']) : ''; ?>"
          placeholder="e.g. you@gmail.com" required>
      </div>
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
            echo "<option value=\"$b\" $sel>$b</option>";
          } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="year_of_study">Year of Study</label>
        <select id="year_of_study" name="year_of_study" required>
          <option value="">-- Select your year --</option>
          <?php foreach ($years as $y) {
            $sel = ($loggedIn && $user['year_of_study'] == $y) ? "selected" : "";
            echo "<option value=\"$y\" $sel>$y</option>";
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
      <button type="submit"><?php echo $loggedIn ? "Save Changes" : "Create Profile"; ?></button>
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