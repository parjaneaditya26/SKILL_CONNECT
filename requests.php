<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>My Requests - Skill Connect</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body data-user="">
    <nav>
      <a href="index.php">Home</a>
      <a href="profile.php">Create Profile</a>
      <a href="login.php">Login</a>
    </nav>
    <header>
      <h1>My Requests</h1>
      <p>Log in to see your connection requests.</p>
    </header>
    <main>
      <div class="empty-state">
        <h3>Please log in</h3>
        <p>You need to be logged in to view your requests.</p>
        <a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Log In</a>
      </div>
    </main>
    <footer>
      <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    </footer>
  </body>
  </html>
  <?php
  exit;
}

$currentUser = $_SESSION['profileName'];
$view = isset($_GET['view']) ? $_GET['view'] : "received";
$cu = $conn->real_escape_string($currentUser);
$csrf = csrf_token();

if ($view == "sent") {
  $sql = "SELECT * FROM requests WHERE requester_name = '$cu'";
} else {
  $sql = "SELECT * FROM requests WHERE target_name = '$cu'";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Requests - Skill Connect</title>
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
    <h1>My Requests</h1>
    <p>See who wants to connect with you, or requests you've sent.</p>
  </header>

  <main>
    <div class="tabs">
      <a href="requests.php?view=received" class="tab <?php echo ($view == 'received') ? 'active' : ''; ?>">Received</a>
      <a href="requests.php?view=sent" class="tab <?php echo ($view == 'sent') ? 'active' : ''; ?>">Sent</a>
    </div>

    <div class="skill-list">
      <?php if ($result->num_rows > 0) { ?>
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
            <p style="font-size: 0.85rem; color: #777;"><?php echo timeAgo($row['created_at']); ?></p>

            <?php if ($row['status'] == "accepted" && $otherMobile) { ?>
              <p><strong>Contact:</strong> <?php echo htmlspecialchars($otherMobile); ?></p>
            <?php } ?>

            <?php if ($view == "received" && $row['status'] == "pending") { ?>
              <div class="request-actions">
                <a href="update_request.php?id=<?php echo $row['id']; ?>&action=accept&csrf=<?php echo $csrf; ?>" class="acceptBtn">Accept</a>
                <a href="update_request.php?id=<?php echo $row['id']; ?>&action=decline&csrf=<?php echo $csrf; ?>" class="declineBtn">Decline</a>
              </div>
            <?php } else { ?>
              <p class="status-label status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></p>
              <?php if ($row['status'] == "accepted" && $otherId) { ?>
                <a href="messages.php?with=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">Message</a>
                <a href="rate.php?id=<?php echo $otherId; ?>&name=<?php echo urlencode($otherName); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;">Rate this person</a>
                <a href="disconnect.php?id=<?php echo $row['id']; ?>&view=<?php echo $view; ?>&csrf=<?php echo $csrf; ?>" class="declineBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;" onclick="return confirm('Disconnect from this person?');">Disconnect</a>
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
  </main>

  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP & MySQL</p>
  </footer>

  <script src="script.js"></script>
</body>
</html>