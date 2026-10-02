<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$id = intval($_GET['id']);
$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : "";

$result = $conn->query("SELECT * FROM users WHERE id = $id");
if ($result->num_rows == 0) {
  echo "Profile not found.";
  exit;
}
$user = $result->fetch_assoc();

$showContact = false;
if ($user['name'] == $currentUser) {
  $showContact = true;
} elseif ($currentUser != "") {
  $cu = $conn->real_escape_string($currentUser);
  $chk = $conn->query("SELECT id FROM requests WHERE status='accepted' AND ((requester_name='$cu' AND target_id=$id) OR (requester_name='{$user['name']}' AND target_name='$cu'))");
  if ($chk->num_rows > 0) { $showContact = true; }
}

$ratings = $conn->query("SELECT * FROM ratings WHERE target_id = $id ORDER BY created_at DESC");
$avgResult = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM ratings WHERE target_id = $id");
$avgRow = $avgResult->fetch_assoc();
$avgRating = $avgRow['avg_rating'] ? round($avgRow['avg_rating'], 1) : null;
$ratingCount = $avgRow['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($user['name']); ?> - Skill Connect</title>
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
      <?php if ($currentUser) { ?>
        <a href="profile.php" class="nav-link">My Profile</a>
        <a href="browse.php" class="nav-link">Browse Skills</a>
        <a href="requests.php" class="nav-link">My Requests</a>
        <a href="connections.php" class="nav-link">My Connections</a>
        <a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
      <?php } else { ?>
        <a href="profile.php" class="nav-link">Create Profile</a>
        <a href="login.php" class="nav-link">Login</a>
      <?php } ?>
    </div>
  </nav>

  <header>
    <h1><?php echo htmlspecialchars($user['name']); ?> <?php if ($user['verified']) { ?><span style="font-size:1.1rem;">✓</span><?php } ?></h1>
    <p><?php echo htmlspecialchars($user['branch']); ?> · <?php echo htmlspecialchars($user['year_of_study']); ?></p>
  </header>

  <main>
    <div class="skill-card" style="margin: 0 auto 10px; max-width: 350px;">
      <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
      <span class="category-tag"><?php echo htmlspecialchars($user['branch']); ?></span>
      <span class="category-tag"><?php echo htmlspecialchars($user['year_of_study']); ?></span>
      <?php if ($avgRating) { ?>
        <p class="rating-display">⭐ <?php echo $avgRating; ?> (<?php echo $ratingCount; ?> reviews)</p>
      <?php } else { ?>
        <p class="rating-display no-rating">No ratings yet</p>
      <?php } ?>
      <p style="font-size:0.82rem; color:#64748b; font-weight:600; margin-bottom:4px;">Can teach:</p>
      <div class="skill-tags-row"><?php echo skillTags($user['teach_skill']); ?></div>
      <p style="font-size:0.82rem; color:#64748b; font-weight:600; margin-bottom:4px;">Wants to learn:</p>
      <div class="skill-tags-row"><?php echo skillTags($user['learn_skill']); ?></div>
      <?php if ($showContact) { ?>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
      <?php } else { ?>
        <p class="locked-contact">🔒 Contact visible after connection is accepted</p>
      <?php } ?>
    </div>

    <?php if ($currentUser && $currentUser != $user['name']) { ?>
      <p style="text-align:center; margin-bottom:30px;">
        <a href="report_user.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['name']); ?>" style="color:#94a3b8; font-size:0.82rem;">Report this profile</a>
      </p>
    <?php } ?>

    <h2 style="color:#0f172a; margin-bottom:18px; font-size:1.3rem;">Reviews (<?php echo $ratingCount; ?>)</h2>
    <div class="skill-list">
      <?php if ($ratings->num_rows > 0) { ?>
        <?php while ($r = $ratings->fetch_assoc()) { ?>
          <div class="skill-card">
            <p class="rating-display">⭐ <?php echo $r['rating']; ?>/5</p>
            <h3 style="font-size:0.98rem;"><?php echo htmlspecialchars($r['rater_name']); ?></h3>
            <?php if ($r['comment']) { ?>
              <p>"<?php echo htmlspecialchars($r['comment']); ?>"</p>
            <?php } ?>
            <p style="font-size:0.78rem; color:#94a3b8;"><?php echo $r['created_at']; ?></p>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="empty-state">
          <h3>No reviews yet</h3>
          <p>Be the first to leave feedback after connecting with <?php echo htmlspecialchars($user['name']); ?>.</p>
        </div>
      <?php } ?>
    </div>
  </main>

  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>