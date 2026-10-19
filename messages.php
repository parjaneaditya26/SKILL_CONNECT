<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Messages", "Log in to chat with your connections.");
}

$currentUser = $_SESSION['profileName'];
$withUser = isset($_GET['with']) ? $_GET['with'] : "";

$cu = $conn->real_escape_string($currentUser);
$wu = $conn->real_escape_string($withUser);

$connectionCheck = $conn->query("SELECT id FROM requests WHERE status='accepted' AND ((requester_name='$cu' AND target_name='$wu') OR (requester_name='$wu' AND target_name='$cu'))");
if (!$connectionCheck || $connectionCheck->num_rows == 0) {
  layoutStart("Messages", "Direct Messages", "You can only message people you're connected with.");
  echo '<div class="empty-state"><h3>Not connected yet</h3><p>Send and accept a connection request first to start messaging.</p><a href="connections.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Back to My Connections</a></div>';
  layoutEnd();
  exit;
}

$messages = $conn->query("SELECT * FROM messages WHERE (sender_name='$cu' AND receiver_name='$wu') OR (sender_name='$wu' AND receiver_name='$cu') ORDER BY created_at ASC");

layoutStart("Chat with " . $withUser, "Chat with " . $withUser, "Coordinate your skill swap or study session.");
?>
    <p style="margin-bottom:14px;"><a href="connections.php" class="btn-outline">← Back to My Connections</a></p>

    <div style="max-width:720px; margin:0; background:white; border-radius:14px; border:1px solid var(--border); padding:20px; text-align:left; max-height:420px; overflow-y:auto;">
      <?php if ($messages && $messages->num_rows > 0) { ?>
        <?php while ($m = $messages->fetch_assoc()) { ?>
          <?php $isMine = ($m['sender_name'] == $currentUser); ?>
          <div style="margin-bottom:12px; text-align:<?php echo $isMine ? 'right' : 'left'; ?>;">
            <div style="display:inline-block; max-width:80%; padding:10px 14px; border-radius:14px; background:<?php echo $isMine ? 'var(--accent)' : 'var(--bg-soft)'; ?>; color:<?php echo $isMine ? 'white' : '#333'; ?>;">
              <?php echo htmlspecialchars($m['message']); ?>
            </div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;"><?php echo timeAgo($m['created_at']); ?></div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <p style="color:var(--text-muted); text-align:center;">No messages yet. Say hello!</p>
      <?php } ?>
    </div>

    <form action="send_message.php" method="POST" style="max-width:720px; margin:16px 0 0;">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="receiver_name" value="<?php echo htmlspecialchars($withUser); ?>">
      <div class="form-group">
        <label for="message">Message</label>
        <input type="text" id="message" name="message" placeholder="Type a message..." required maxlength="500">
      </div>
      <button type="submit">Send Message</button>
    </form>
<?php layoutEnd(); ?>