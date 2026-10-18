<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

$id = intval($_GET['id'] ?? 0);
$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : "";

$result = $conn->query("SELECT * FROM users WHERE id = $id");
if (!$result || $result->num_rows == 0) {
  layoutStart("Profile Not Found", "Profile Not Found", "This user profile could not be found.");
  echo '<div class="empty-state"><h3>User not found</h3><p><a href="browse.php" class="connectBtn" style="text-decoration:none; display:inline-block; margin-top:10px;">Back to Browse Skills</a></p></div>';
  layoutEnd();
  exit;
}
$user = $result->fetch_assoc();

$showContact = false;
if ($user['name'] == $currentUser) {
  $showContact = true;
} elseif ($currentUser != "") {
  $cu = $conn->real_escape_string($currentUser);
  $un = $conn->real_escape_string($user['name']);
  $chk = $conn->query("SELECT id FROM requests WHERE status='accepted' AND ((requester_name='$cu' AND target_id=$id) OR (requester_name='$un' AND target_name='$cu'))");
  if ($chk && $chk->num_rows > 0) { $showContact = true; }
}

$ratings = $conn->query("SELECT * FROM ratings WHERE target_id = $id ORDER BY created_at DESC");
$avgResult = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM ratings WHERE target_id = $id");
$avgRow = $avgResult->fetch_assoc();
$avgRating = $avgRow['avg_rating'] ? round($avgRow['avg_rating'], 1) : null;
$ratingCount = $avgRow['total'];

$heading = $user['name'] . ($user['verified'] ? ' ✓' : '');
$sub = $user['branch'] . ' · ' . $user['year_of_study'];
layoutStart($user['name'], $heading, $sub);
?>
    <div class="skill-card profile-hero-card">
      <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
      <span class="category-tag"><?php echo htmlspecialchars($user['branch']); ?></span>
      <span class="category-tag"><?php echo htmlspecialchars($user['year_of_study']); ?></span>
      <?php if ($avgRating) { ?>
        <p class="rating-display">⭐ <?php echo $avgRating; ?> (<?php echo $ratingCount; ?> reviews)</p>
      <?php } else { ?>
        <p class="rating-display no-rating">No ratings yet</p>
      <?php } ?>
      <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; margin-bottom:4px;">Can teach:</p>
      <div class="skill-tags-row"><?php echo skillTags($user['teach_skill']); ?></div>
      <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; margin-bottom:4px;">Wants to learn:</p>
      <div class="skill-tags-row"><?php echo skillTags($user['learn_skill']); ?></div>
      <?php if ($showContact) { ?>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
      <?php } else { ?>
        <p class="locked-contact">🔒 Contact visible after connection is accepted</p>
      <?php } ?>
    </div>

    <?php if ($currentUser && $currentUser != $user['name']) { ?>
      <p style="margin-bottom:28px;">
        <a href="report_user.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['name']); ?>" class="btn-outline danger" style="font-size:0.82rem; padding:6px 12px;">🚩 Report this profile</a>
      </p>
    <?php } ?>

    <h2 style="color:var(--text-dark); margin-bottom:18px; font-size:1.3rem;">Reviews (<?php echo $ratingCount; ?>)</h2>
    <div class="skill-list">
      <?php if ($ratings && $ratings->num_rows > 0) { ?>
        <?php while ($r = $ratings->fetch_assoc()) { ?>
          <div class="skill-card">
            <p class="rating-display">⭐ <?php echo $r['rating']; ?>/5</p>
            <h3 style="font-size:0.98rem;"><?php echo htmlspecialchars($r['rater_name']); ?></h3>
            <?php if ($r['comment']) { ?>
              <p>"<?php echo htmlspecialchars($r['comment']); ?>"</p>
            <?php } ?>
            <p style="font-size:0.78rem; color:var(--text-muted);"><?php echo $r['created_at']; ?></p>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="empty-state">
          <h3>No reviews yet</h3>
          <p>Be the first to leave feedback after connecting with <?php echo htmlspecialchars($user['name']); ?>.</p>
        </div>
      <?php } ?>
    </div>
<?php layoutEnd(); ?>