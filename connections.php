<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$currentUser = $_SESSION['profileName'];
$cu = $conn->real_escape_string($currentUser);
$csrf = csrf_token();

$result = $conn->query("SELECT * FROM requests WHERE status = 'accepted' AND (requester_name = '$cu' OR target_name = '$cu') ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Connections - Skill Connect</title>
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
    <h1>My Connections</h1>
    <p>Everyone you're currently connected with.</p>
  </header>
  <main>
    <div class="skill-list">
      <?php if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <?php
          $otherName = ($row['requester_name'] == $currentUser) ? $row['target_name'] : $row['requester_name'];
          $on = $conn->real_escape_string($otherName);
          $otherUser = $conn->query("SELECT * FROM users WHERE name = '$on'");
          if ($otherUser->num_rows == 0) { continue; }
          $ou = $otherUser->fetch_assoc();
          ?>
          <div class="skill-card">
            <div class="avatar"><?php echo strtoupper(substr($otherName, 0, 1)); ?></div>
            <span class="category-tag"><?php echo htmlspecialchars($ou['branch']); ?></span>
            <h3><?php echo htmlspecialchars($otherName); ?></h3>
            <p style="font-size:0.82rem; color:#64748b; font-weight:600; margin-bottom:4px;">Can teach:</p>
            <div class="skill-tags-row"><?php echo skillTags($ou['teach_skill']); ?></div>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($ou['mobile']); ?></p>
            <a href="messages.php?with=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">Message</a>
            <a href="rate.php?id=<?php echo $ou['id']; ?>&name=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;">Rate</a>
            <a href="disconnect.php?id=<?php echo $row['id']; ?>&view=received&csrf=<?php echo $csrf; ?>" class="declineBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;" onclick="return confirm('Disconnect from this person?');">Disconnect</a>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="empty-state">
          <h3>No connections yet</h3>
          <p>Browse skills and connect with people to see them here.</p>
          <a href="browse.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Browse Skills</a>
        </div>
      <?php } ?>
    </div>
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