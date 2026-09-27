<?php
// messages.php
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
    <a href="index.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="browse.php">Browse Skills</a>
    <a href="requests.php">My Requests</a>
    <a href="connections.php">My Connections</a>
    <a href="logout.php">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
  </nav>
  <header>
    <h1>Chat with <?php echo htmlspecialchars($withUser); ?></h1>
  </header>
  <main>
    <div style="max-width:500px; margin:0 auto; background:white; border-radius:16px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:20px; text-align:left;">
      <?php if ($messages->num_rows > 0) { ?>
        <?php while ($m = $messages->fetch_assoc()) { ?>
          <?php $isMine = ($m['sender_name'] == $currentUser); ?>
          <div style="margin-bottom:12px; text-align:<?php echo $isMine ? 'right' : 'left'; ?>;">
            <div style="display:inline-block; max-width:80%; padding:10px 14px; border-radius:14px; background:<?php echo $isMine ? '#1d4ed8' : '#eceff1'; ?>; color:<?php echo $isMine ? 'white' : '#333'; ?>;">
              <?php echo htmlspecialchars($m['message']); ?>
            </div>
            <div style="font-size:0.75rem; color:#999; margin-top:2px;"><?php echo timeAgo($m['created_at']); ?></div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <p style="color:#999; text-align:center;">No messages yet. Say hello!</p>
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
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>