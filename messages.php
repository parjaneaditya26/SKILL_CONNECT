<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$currentUser = $_SESSION['profileName'];
$withUser = isset($_GET['with']) ? $_GET['with'] : "";

$cu = $conn->real_escape_string($currentUser);
$wu = $conn->real_escape_string($withUser);

$connectionCheck = $conn->query("SELECT id FROM requests WHERE status='accepted' AND ((requester_name='$cu' AND target_name='$wu') OR (requester_name='$wu' AND target_name='$cu'))");
if ($connectionCheck->num_rows == 0) {
  echo "<p>You can only message people you're connected with.</p>";
  echo "<a href='connections.php'>Back to My Connections</a>";
  exit;
}

$messages = $conn->query("SELECT * FROM messages WHERE (sender_name='$cu' AND receiver_name='$wu') OR (sender_name='$wu' AND receiver_name='$cu') ORDER BY created_at ASC");
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat with <?php echo htmlspecialchars($withUser); ?> - Skill Connect</title>
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
    <h1>Chat with <?php echo htmlspecialchars($withUser); ?></h1>
  </header>
  <main>
    <div style="max-width:500px; margin:0 auto; background:white; border-radius:14px; border:1px solid #e2e8f0; padding:20px; text-align:left;">
      <?php if ($messages->num_rows > 0) { ?>
        <?php while ($m = $messages->fetch_assoc()) { ?>
          <?php $isMine = ($m['sender_name'] == $currentUser); ?>
          <div style="margin-bottom:12px; text-align:<?php echo $isMine ? 'right' : 'left'; ?>;">
            <div style="display:inline-block; max-width:80%; padding:10px 14px; border-radius:14px; background:<?php echo $isMine ? '#0d9488' : '#f1f5f9'; ?>; color:<?php echo $isMine ? 'white' : '#333'; ?>;">
              <?php echo htmlspecialchars($m['message']); ?>
            </div>
            <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;"><?php echo timeAgo($m['created_at']); ?></div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <p style="color:#94a3b8; text-align:center;">No messages yet. Say hello!</p>
      <?php } ?>
    </div>

    <form action="send_message.php" method="POST" style="max-width:500px;">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="receiver_name" value="<?php echo htmlspecialchars($withUser); ?>">
      <div class="form-group">
        <label for="message">Message</label>
        <input type="text" id="message" name="message" placeholder="Type a message..." required maxlength="500">
      </div>
      <button type="submit">Send</button>
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
  <script src="script.js"></script>
</body>
</html>