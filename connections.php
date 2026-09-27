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
    <a href="index.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="browse.php">Browse Skills</a>
    <a href="requests.php">My Requests</a>
    <a href="connections.php">My Connections</a>
    <a href="logout.php">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
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
            <span class="category-tag"><?php echo htmlspecialchars($ou['category']); ?></span>
            <h3><?php echo htmlspecialchars($otherName); ?></h3>
            <p><strong>Can teach:</strong> <?php echo htmlspecialchars($ou['teach_skill']); ?></p>
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
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>