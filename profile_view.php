<?php
session_start();
include 'db_connect.php';

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
    <a href="index.php">Home</a>
    <?php if ($currentUser) { ?>
      <a href="profile.php">My Profile</a>
      <a href="browse.php">Browse Skills</a>
      <a href="requests.php">My Requests</a>
      <a href="logout.php">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
    <?php } else { ?>
      <a href="profile.php">Create Profile</a>
      <a href="login.php">Login</a>
    <?php } ?>
  </nav>

  <header>
    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
    <p><?php echo htmlspecialchars($user['category']); ?></p>
  </header>

  <main>
    <div class="skill-card" style="margin: 0 auto 30px; max-width: 350px;">
      <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
      <span class="category-tag"><?php echo htmlspecialchars($user['category']); ?></span>
      <?php if ($avgRating) { ?>
        <p class="rating-display">⭐ <?php echo $avgRating; ?> (<?php echo $ratingCount; ?> reviews)</p>
      <?php } else { ?>
        <p class="rating-display no-rating">No ratings yet</p>
      <?php } ?>
      <p><strong>Can teach:</strong> <?php echo htmlspecialchars($user['teach_skill']); ?></p>
      <p><strong>Wants to learn:</strong> <?php echo htmlspecialchars($user['learn_skill']); ?></p>
      <?php if ($showContact) { ?>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
      <?php } else { ?>
        <p class="locked-contact">🔒 Contact visible after connection is accepted</p>
      <?php } ?>
    </div>

    <h2 style="color:#14323b; margin-bottom:20px;">Reviews (<?php echo $ratingCount; ?>)</h2>
    <div class="skill-list">
      <?php if ($ratings->num_rows > 0) { ?>
        <?php while ($r = $ratings->fetch_assoc()) { ?>
          <div class="skill-card">
            <p class="rating-display">⭐ <?php echo $r['rating']; ?>/5</p>
            <h3 style="font-size:1rem;"><?php echo htmlspecialchars($r['rater_name']); ?></h3>
            <?php if ($r['comment']) { ?>
              <p>"<?php echo htmlspecialchars($r['comment']); ?>"</p>
            <?php } ?>
            <p style="font-size:0.8rem; color:#999;"><?php echo $r['created_at']; ?></p>
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
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>