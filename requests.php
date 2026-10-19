<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("My Requests", "Log in to see your incoming and outgoing connection requests.");
}

$currentUser = $_SESSION['profileName'];
$view = isset($_GET['view']) ? $_GET['view'] : "received";
$cu = $conn->real_escape_string($currentUser);

if ($view == "sent") {
  $sql = "SELECT * FROM requests WHERE requester_name = '$cu' ORDER BY id DESC";
} else {
  $sql = "SELECT * FROM requests WHERE target_name = '$cu' ORDER BY id DESC";
}
$result = $conn->query($sql);

layoutStart("My Requests", "My Requests", "See who wants to connect with you, or requests you've sent.");
?>
    <div class="tabs">
      <a href="requests.php?view=received" class="tab <?php echo ($view == 'received') ? 'active' : ''; ?>">Received</a>
      <a href="requests.php?view=sent" class="tab <?php echo ($view == 'sent') ? 'active' : ''; ?>">Sent</a>
    </div>

    <div class="skill-list">
      <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <?php
          $otherName = ($view == "received") ? $row['requester_name'] : $row['target_name'];
          $otherMobile = "";
          $otherId = null;

          $on = $conn->real_escape_string($otherName);
          $otherUser = $conn->query("SELECT id, mobile FROM users WHERE name = '$on'");
          if ($otherUser && $otherUser->num_rows > 0) {
            $ou = $otherUser->fetch_assoc();
            $otherId = $ou['id'];
            if ($row['status'] == "accepted") {
              $otherMobile = $ou['mobile'];
            }
          }
          ?>
          <div class="skill-card">
            <div class="avatar"><?php echo strtoupper(substr($otherName, 0, 1)); ?></div>
            <h3><?php echo htmlspecialchars($otherName); ?></h3>
            <?php if ($view == "received") { ?>
              <p>wants to connect with you!</p>
            <?php } else { ?>
              <p>You sent a request to connect.</p>
            <?php } ?>
            <p style="font-size: 0.85rem; color: var(--text-muted);"><?php echo timeAgo($row['created_at']); ?></p>

            <?php if ($row['status'] == "accepted" && $otherMobile) { ?>
              <p><strong>Contact:</strong> <?php echo htmlspecialchars($otherMobile); ?></p>
            <?php } ?>

            <?php if ($view == "received" && $row['status'] == "pending") { ?>
              <div class="request-actions">
                <form class="ajax-form inline-form" action="update_request.php">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="action" value="accept">
                  <button type="submit" class="acceptBtn" style="width:100%;">Accept</button>
                </form>
                <form class="ajax-form inline-form" action="update_request.php">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="action" value="decline">
                  <button type="submit" class="declineBtn" style="width:100%;">Decline</button>
                </form>
              </div>
            <?php } else { ?>
              <p class="status-label status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></p>
              <?php if ($row['status'] == "accepted" && $otherId) { ?>
                <a href="messages.php?with=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">Message</a>
                <a href="rate.php?id=<?php echo $otherId; ?>&name=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;">Rate this person</a>
                <form class="ajax-form inline-form" action="disconnect.php" style="margin-top:8px;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <button type="submit" class="declineBtn" style="width:100%;" onclick="return confirm('Disconnect from this person?');">Disconnect</button>
                </form>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="empty-state">
          <h3>No requests here</h3>
          <p><?php echo ($view == "sent") ? "You haven't sent any requests yet." : "No one has sent you a request yet."; ?></p>
        </div>
      <?php } ?>
    </div>
<?php layoutEnd(); ?>