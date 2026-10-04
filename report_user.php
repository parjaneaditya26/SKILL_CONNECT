<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$target_id = intval($_GET['id']);
$target_name = $_GET['name'];
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report <?php echo htmlspecialchars($target_name); ?> - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo htmlspecialchars($_SESSION['profileName']); ?>">
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <a href="profile.php" class="nav-link">My Profile</a>
      <a href="browse.php" class="nav-link">Browse Skills</a>
    </div>
  </nav>
  <header>
    <h1>Report <?php echo htmlspecialchars($target_name); ?></h1>
    <p>Let us know if something's wrong with this profile.</p>
  </header>
  <main>
    <form action="save_report.php" method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
      <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($target_name); ?>">
      <div class="form-group">
        <label for="reason">Reason</label>
        <select id="reason" name="reason" required>
          <option value="Spam">Spam</option>
          <option value="Inappropriate behavior">Inappropriate behavior</option>
          <option value="Fake profile">Fake profile</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label for="details">Details (optional)</label>
        <input type="text" id="details" name="details" placeholder="Tell us more...">
      </div>
      <button type="submit">Submit Report</button>
    </form>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
  </footer>
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
</body>
</html>