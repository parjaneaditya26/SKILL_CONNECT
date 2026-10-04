<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$target_id = intval($_GET['id']);
$target_name = $_GET['name'];
$currentUser = $_SESSION['profileName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rate <?php echo htmlspecialchars($target_name); ?> - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo htmlspecialchars($currentUser); ?>">
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <a href="profile.php" class="nav-link">My Profile</a>
      <a href="browse.php" class="nav-link">Browse Skills</a>
      <a href="requests.php" class="nav-link">My Requests</a>
      <a href="connections.php" class="nav-link">My Connections</a>
      <a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
    </div>
  </nav>
  <header>
    <h1>Rate <?php echo htmlspecialchars($target_name); ?></h1>
    <p>Share your experience learning from them.</p>
  </header>
  <main>
    <form action="save_rating.php" method="POST">
      <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
      <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($target_name); ?>">
      <div class="form-group">
        <label for="rating">Rating</label>
        <select id="rating" name="rating" required>
          <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
          <option value="4">⭐⭐⭐⭐ Good</option>
          <option value="3">⭐⭐⭐ Average</option>
          <option value="2">⭐⭐ Below Average</option>
          <option value="1">⭐ Poor</option>
        </select>
      </div>
      <div class="form-group">
        <label for="comment">Comment (optional)</label>
        <input type="text" id="comment" name="comment" placeholder="How was your experience?">
      </div>
      <button type="submit">Submit Rating</button>
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