<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: index.php");
  exit;
}

$currentUser = $_SESSION['profileName'];

$s = $conn->prepare("SELECT * FROM users WHERE name = ?");
$s->bind_param("s", $currentUser);
$s->execute();
$me = $s->get_result()->fetch_assoc();
$s->close();

if (!$me) {
  session_destroy();
  header("Location: index.php");
  exit;
}

$s = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE target_name = ? AND status = 'pending'");
$s->bind_param("s", $currentUser);
$s->execute();
$pendingCount = $s->get_result()->fetch_assoc()['c'];
$s->close();

$s = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE status = 'accepted' AND (requester_name = ? OR target_name = ?)");
$s->bind_param("ss", $currentUser, $currentUser);
$s->execute();
$connectionCount = $s->get_result()->fetch_assoc()['c'];
$s->close();

$s = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE target_id = ?");
$s->bind_param("i", $me['id']);
$s->execute();
$ratingRow = $s->get_result()->fetch_assoc();
$s->close();
$avgRating = $ratingRow['avg_rating'] ? round($ratingRow['avg_rating'], 1) : null;

$announcements = $conn->query("SELECT *, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE (deadline IS NULL OR deadline >= CURDATE()) ORDER BY pinned DESC, created_at DESC, id DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$helpPosts = $conn->query("SELECT p.*, u.id AS author_id FROM help_posts p LEFT JOIN users u ON u.name = p.author_name WHERE p.status = 'open' ORDER BY p.created_at DESC, p.id DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

$matches = [];
$s = $conn->prepare("SELECT id, name, branch, year_of_study, teach_skill, learn_skill FROM users WHERE name <> ?");
$s->bind_param("s", $currentUser);
$s->execute();
$others = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

$candidates = [];
foreach ($others as $o) {
  $theyTeach = matchingSkills($me['learn_skill'], $o['teach_skill']);
  if (empty($theyTeach)) { continue; }
  $iTeach = matchingSkills($o['learn_skill'], $me['teach_skill']);
  $o['they_teach'] = $theyTeach;
  $o['i_teach'] = $iTeach;
  $o['score'] = 2 + (empty($iTeach) ? 0 : 1);
  $candidates[] = $o;
}
usort($candidates, function ($a, $b) {
  if ($a['score'] != $b['score']) { return $b['score'] - $a['score']; }
  return count($b['they_teach']) - count($a['they_teach']);
});
foreach ($candidates as $c) {
  $st = connectionStatus($conn, $currentUser, $c['name']);
  if ($st === 'accepted' || $st === 'pending') { continue; }
  $matches[] = $c;
  if (count($matches) >= 3) { break; }
}

$helpLabels = ["teammates" => "Looking for teammates", "help" => "Needs help"];

layoutStart("Home", "Welcome back, " . $currentUser, "Here's what's happening on campus.");
?>
    <?php if (!$me['verified']) { ?>
      <div class="notice">Your email isn't verified yet. <a href="profile.php">Verify it from your profile</a> to receive announcement emails.</div>
    <?php } ?>

    <div class="dash-stats">
      <a class="stat-box" href="requests.php">
        <div class="stat-number"><?php echo $pendingCount; ?></div>
        <div class="stat-label">Pending requests</div>
      </a>
      <a class="stat-box" href="connections.php">
        <div class="stat-number"><?php echo $connectionCount; ?></div>
        <div class="stat-label">Connections</div>
      </a>
      <a class="stat-box" href="profile_view.php?id=<?php echo $me['id']; ?>">
        <div class="stat-number"><?php echo $avgRating ? $avgRating . ' ★' : '—'; ?></div>
        <div class="stat-label">Your rating (<?php echo $ratingRow['total']; ?> reviews)</div>
      </a>
    </div>

    <div class="dash-grid">
      <section>
        <div class="dash-section-head">
          <h2>Latest announcements</h2>
          <a href="announcements.php">View all →</a>
        </div>
        <?php if (count($announcements) > 0) { ?>
          <div class="dash-stack">
            <?php foreach ($announcements as $a) { $info = deadlineInfo($a['days_left']); ?>
              <div class="announce-card <?php echo $a['pinned'] ? 'is-pinned' : ''; ?>">
                <div class="announce-top">
                  <span class="category-tag"><?php echo h($a['category']); ?></span>
                  <?php if ($a['pinned']) { ?><span class="pin-badge">📌 Pinned</span><?php } ?>
                  <?php if ($info) { ?><span class="deadline-tag <?php echo $info[1]; ?>"><?php echo h($info[0]); ?></span><?php } ?>
                </div>
                <h3><a href="announcements.php?category=<?php echo urlencode($a['category']); ?>"><?php echo h($a['title']); ?></a></h3>
                <p class="announce-body"><?php echo h(textLen($a['body']) > 140 ? textCut($a['body'], 140) . '…' : $a['body']); ?></p>
                <p class="announce-meta">Posted <?php echo timeAgo($a['created_at']); ?></p>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="empty-state" style="margin:0;"><h3>No announcements yet</h3><p>New notices from your college will show up here.</p></div>
        <?php } ?>
      </section>

      <aside>
        <div class="dash-section-head">
          <h2>Suggested for you</h2>
          <a href="browse.php">Browse all →</a>
        </div>
        <?php if (count($matches) > 0) { ?>
          <div class="dash-stack">
            <?php foreach ($matches as $m) { ?>
              <div class="skill-card match-card">
                <div class="avatar"><?php echo strtoupper(substr($m['name'], 0, 1)); ?></div>
                <div class="match-body">
                  <h3><a href="profile_view.php?id=<?php echo $m['id']; ?>" style="color:inherit; text-decoration:none;"><?php echo h($m['name']); ?></a></h3>
                  <p class="announce-meta"><?php echo h($m['branch']); ?> · <?php echo h($m['year_of_study']); ?></p>
                  <p>Teaches <strong><?php echo h(implode(', ', $m['they_teach'])); ?></strong>, which you want to learn.</p>
                  <?php if (!empty($m['i_teach'])) { ?>
                    <p class="announce-meta">✨ Two-way match: they want to learn <?php echo h(implode(', ', $m['i_teach'])); ?> from you.</p>
                  <?php } ?>
                  <form class="ajax-form inline-form" action="connect.php" method="POST" style="margin-top:6px;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="target_id" value="<?php echo $m['id']; ?>">
                    <input type="hidden" name="target_name" value="<?php echo h($m['name']); ?>">
                    <button type="submit" class="btn-outline">Connect</button>
                  </form>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="empty-state" style="margin:0;"><h3>No matches yet</h3><p>Add the skills you want to learn on your profile and we'll suggest people who teach them.</p></div>
        <?php } ?>

        <div class="dash-section-head" style="margin-top:28px;">
          <h2>Help board</h2>
          <a href="help_board.php">Open →</a>
        </div>
        <?php if (count($helpPosts) > 0) { ?>
          <div class="dash-stack">
            <?php foreach ($helpPosts as $p) { ?>
              <div class="help-card">
                <div class="announce-top">
                  <span class="help-type-tag type-<?php echo h($p['type']); ?>"><?php echo h($helpLabels[$p['type']] ?? $p['type']); ?></span>
                </div>
                <h3><a href="help_board.php?type=<?php echo h($p['type']); ?>"><?php echo h($p['title']); ?></a></h3>
                <p class="announce-meta"><?php echo h($p['author_name']); ?> · <?php echo timeAgo($p['created_at']); ?></p>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="empty-state" style="margin:0;"><h3>Nothing posted</h3><p>Ask for teammates or help from classmates.</p></div>
        <?php } ?>
      </aside>
    </div>
<?php layoutEnd(); ?>
