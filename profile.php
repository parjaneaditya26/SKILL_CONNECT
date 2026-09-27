<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$loggedIn = isset($_SESSION['profileName']);
$user = null;

if ($loggedIn) {
  $name = $_SESSION['profileName'];
  $result = $conn->query("SELECT * FROM users WHERE name = '" . $conn->real_escape_string($name) . "'");
  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
  } else {
    session_destroy();
    $loggedIn = false;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo $loggedIn ? htmlspecialchars($user['name']) : ''; ?>">
  <nav>
    <a href="index.php">Home</a>
    <?php if ($loggedIn) { ?>
      <a href="profile.php">My Profile</a>
      <a href="browse.php">Browse Skills</a>
      <a href="requests.php">My Requests</a>
      <a href="connections.php">My Connections</a>
      <a href="logout.php">Logout (<?php echo htmlspecialchars($user['name']); ?>)</a>
    <?php } else { ?>
      <a href="profile.php">Create Profile</a>
      <a href="login.php">Login</a>
    <?php } ?>
  </nav>

  <header>
    <h1><?php echo $loggedIn ? "Edit My Profile" : "Create Your Profile"; ?></h1>
    <p><?php echo $loggedIn ? "Update your skills or contact details." : "Tell others what you can teach and what you want to learn."; ?></p>
  </header>

  <?php if ($loggedIn) { ?>
    <div style="max-width:420px; margin: 0 auto 10px; text-align:center;">
      <?php if ($user['verified']) { ?>
        <p style="color:#1d4ed8; font-weight:600; font-size:0.9rem;">✓ Email Verified</p>
      <?php } else { ?>
        <p style="color:#b8860b; font-weight:600; font-size:0.9rem;">
          Email not verified — <a href="resend_verification.php" style="color:#1d4ed8;">Resend verification email</a>
        </p>
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
        <label for="teachSkill">Skill You Can Teach</label>
        <input type="text" id="teachSkill" name="teachSkill"
          value="<?php echo ($loggedIn && $user['teach_skill'] != 'Nothing yet') ? htmlspecialchars($user['teach_skill']) : ''; ?>"
          placeholder="e.g. Guitar (optional)">
      </div>
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" required>
          <option value="">-- Select a category --</option>
          <?php
          $cats = ["Music","Tech","Cooking","Sports","Language","Art","Other"];
          foreach ($cats as $c) {
            $sel = ($loggedIn && $user['category'] == $c) ? "selected" : "";
            echo "<option value=\"$c\" $sel>$c</option>";
          }
          ?>
        </select>
      </div>
      <div class="form-group">
        <label for="learnSkill">Skill You Want to Learn</label>
        <input type="text" id="learnSkill" name="learnSkill"
          value="<?php echo ($loggedIn && $user['learn_skill'] != 'Nothing yet') ? htmlspecialchars($user['learn_skill']) : ''; ?>"
          placeholder="e.g. Photography (optional)">
      </div>
      <button type="submit"><?php echo $loggedIn ? "Save Changes" : "Create Profile"; ?></button>
    </form>

    <?php if (!$loggedIn) { ?>
      <p style="text-align:center; margin-top:16px; font-size:0.9rem; color:#666;">
        Already have a profile? <a href="login.php" style="color:#1d4ed8; font-weight:600;">Log in</a>
      </p>
    <?php } ?>

    <?php if ($loggedIn) { ?>
      <div class="danger-zone">
        <h3>Delete Your Profile</h3>
        <p>Enter your password to permanently remove your profile. Only you can do this.</p>
        <form action="delete_profile.php" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');">
          <?php echo csrf_field(); ?>
          <div class="password-wrap">
            <input type="password" id="deletePassword" name="password" placeholder="Your password" required>
            <button type="button" class="toggle-password" data-target="deletePassword">👁</button>
          </div>
          <button type="submit" class="deleteBtn">Delete Profile</button>
        </form>
      </div>
    <?php } ?>
  </main>

  <script src="script.js"></script>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP & MySQL</p>
  </footer>
</body>
</html>