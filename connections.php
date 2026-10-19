<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("My Connections", "Log in to see everyone you're connected with.");
}

$currentUser = $_SESSION['profileName'];
$cu = $conn->real_escape_string($currentUser);

$result = $conn->query("SELECT * FROM requests WHERE status = 'accepted' AND (requester_name = '$cu' OR target_name = '$cu') ORDER BY id DESC");

layoutStart("My Connections", "My Connections", "Everyone you're currently connected with for skill swapping and mentorship.");
?>
    <div class="skill-list">
      <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <?php
          $otherName = ($row['requester_name'] == $currentUser) ? $row['target_name'] : $row['requester_name'];
          $on = $conn->real_escape_string($otherName);
          $otherUser = $conn->query("SELECT * FROM users WHERE name = '$on'");
          if (!$otherUser || $otherUser->num_rows == 0) { continue; }
          $ou = $otherUser->fetch_assoc();
          ?>
          <div class="skill-card">
            <div class="avatar"><?php echo strtoupper(substr($otherName, 0, 1)); ?></div>
            <span class="category-tag"><?php echo htmlspecialchars($ou['branch']); ?></span>
            <?php if ($ou['year_of_study']) { ?><span class="category-tag"><?php echo htmlspecialchars($ou['year_of_study']); ?></span><?php } ?>
            <h3><a href="profile_view.php?id=<?php echo $ou['id']; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($otherName); ?></a></h3>
            <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; margin-bottom:4px;">Can teach:</p>
            <div class="skill-tags-row"><?php echo skillTags($ou['teach_skill']); ?></div>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($ou['mobile']); ?></p>
            <a href="messages.php?with=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">💬 Message</a>
            <a href="rate.php?id=<?php echo $ou['id']; ?>&name=<?php echo urlencode($otherName); ?>" class="btn-outline" style="display:block; text-align:center; text-decoration:none; margin-top:8px;">⭐ Rate</a>
            <form class="ajax-form inline-form" action="disconnect.php" style="margin-top:8px;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
              <button type="submit" class="declineBtn" style="width:100%;" onclick="return confirm('Disconnect from this person?');">Disconnect</button>
            </form>
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
<?php layoutEnd(); ?>