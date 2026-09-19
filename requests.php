<?php
include 'db_connect.php';

$name = "";
$view = "received"; // default view

if (isset($_GET['name']) && $_GET['name'] != "") {
  $name = $_GET['name'];
}
if (isset($_GET['view'])) {
  $view = $_GET['view'];
}

if ($name != "") {
  if ($view == "sent") {
    $sql = "SELECT * FROM requests WHERE requester_name = '$name'";
  } else {
    $sql = "SELECT * FROM requests WHERE target_name = '$name'";
  }
  $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Requests - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <nav>
    <a href="index.php">Home</a>
    <a href="profile.html">My Profile</a>
    <a href="browse.php">Browse Skills</a>
    <a href="requests.php">My Requests</a>
  </nav>

  <header>
    <h1>My Requests</h1>
    <p>See who wants to connect with you, or requests you've sent.</p>
  </header>

  <main>
    <form class="search-form" action="requests.php" method="GET">
      <input type="text" name="name" placeholder="Enter your name..." value="<?php echo $name; ?>" required>
      <input type="hidden" name="view" value="<?php echo $view; ?>">
      <button type="submit">Check Requests</button>
    </form>

    <?php if ($name != "") { ?>
      <div class="tabs">
        <a href="requests.php?name=<?php echo urlencode($name); ?>&view=received" class="tab <?php echo ($view == 'received') ? 'active' : ''; ?>">Received</a>
        <a href="requests.php?name=<?php echo urlencode($name); ?>&view=sent" class="tab <?php echo ($view == 'sent') ? 'active' : ''; ?>">Sent</a>
      </div>
    <?php } ?>

    <div class="skill-list">
      <?php if ($name != "") { ?>
        <?php if ($result->num_rows > 0) { ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="skill-card">
              <?php if ($view == "received") { ?>
                <div class="avatar"><?php echo strtoupper(substr($row['requester_name'], 0, 1)); ?></div>
                <h3><?php echo $row['requester_name']; ?></h3>
                <p>wants to connect with you!</p>
              <?php } else { ?>
                <div class="avatar"><?php echo strtoupper(substr($row['target_name'], 0, 1)); ?></div>
                <h3><?php echo $row['target_name']; ?></h3>
                <p>You sent a request to connect.</p>
              <?php } ?>
              <p style="font-size: 0.85rem; color: #777;"><?php echo $row['created_at']; ?></p>

              <?php if ($view == "received" && $row['status'] == "pending") { ?>
                <div class="request-actions">
                  <a href="update_request.php?id=<?php echo $row['id']; ?>&action=accept&name=<?php echo urlencode($name); ?>" class="acceptBtn">Accept</a>
                  <a href="update_request.php?id=<?php echo $row['id']; ?>&action=decline&name=<?php echo urlencode($name); ?>" class="declineBtn">Decline</a>
                </div>
              <?php } else { ?>
                <p class="status-label status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></p>
                <?php if ($row['status'] == "accepted") { ?>
                  <a href="rate.php?id=<?php echo $row['target_id']; ?>&name=<?php echo urlencode($row['target_name']); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:10px;">Rate this person</a>
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