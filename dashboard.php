<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once 'db_connect.php';
include_once 'helpers.php';

ensureSchema($conn);

if (!isset($_SESSION['profileName'])) {
  header("Location: index.php");
  exit;
}

$currentUser = $_SESSION['profileName'];

$me = null;
$s = $conn->prepare("SELECT * FROM users WHERE name = ?");
if ($s) {
  $s->bind_param("s", $currentUser);
  $s->execute();
  $resMe = $s->get_result();
  if ($resMe) { $me = $resMe->fetch_assoc(); }
  $s->close();
}

if (!$me) {
  session_destroy();
  header("Location: index.php");
  exit;
}

$pendingCount = 0;
$s = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE target_name = ? AND status = 'pending'");
if ($s) {
  $s->bind_param("s", $currentUser);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $pendingCount = intval($r->fetch_assoc()['c']); }
  $s->close();
}

$connectionCount = 0;
$s = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE status = 'accepted' AND (requester_name = ? OR target_name = ?)");
if ($s) {
  $s->bind_param("ss", $currentUser, $currentUser);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $connectionCount = intval($r->fetch_assoc()['c']); }
  $s->close();
}

$ratingRow = ['avg_rating' => null, 'total' => 0];
$s = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE target_id = ?");
if ($s) {
  $s->bind_param("i", $me['id']);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $ratingRow = $r->fetch_assoc(); }
  $s->close();
}
$avgRating = $ratingRow['avg_rating'] ? round($ratingRow['avg_rating'], 1) : null;

$savedNoticeCount = 0;
$s = $conn->prepare("SELECT COUNT(*) AS c FROM saved_announcements WHERE user_name = ?");
if ($s) {
  $s->bind_param("s", $currentUser);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $savedNoticeCount = intval($r->fetch_assoc()['c']); }
  $s->close();
}

$savedMap = [];
$ss = $conn->prepare("SELECT announcement_id FROM saved_announcements WHERE user_name = ?");
if ($ss) {
  $ss->bind_param("s", $currentUser);
  $ss->execute();
  $sr = $ss->get_result();
  if ($sr) {
    while ($r = $sr->fetch_assoc()) { $savedMap[$r['announcement_id']] = true; }
  }
  $ss->close();
}

$annQuery = $conn->query("SELECT *, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE (deadline IS NULL OR deadline >= CURDATE()) ORDER BY pinned DESC, created_at DESC, id DESC LIMIT 3");
$announcements = $annQuery ? $annQuery->fetch_all(MYSQLI_ASSOC) : [];

$psQuery = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM peer_session_participants p WHERE p.session_id = s.id) AS joined_count FROM peer_sessions s WHERE s.status = 'upcoming' ORDER BY s.featured DESC, s.session_date ASC LIMIT 3");
$peerSessions = $psQuery ? $psQuery->fetch_all(MYSQLI_ASSOC) : [];

$hpQuery = $conn->query("SELECT p.*, u.id AS author_id FROM help_posts p LEFT JOIN users u ON u.name = p.author_name WHERE p.status = 'open' ORDER BY p.created_at DESC, p.id DESC LIMIT 3");
$helpPosts = $hpQuery ? $hpQuery->fetch_all(MYSQLI_ASSOC) : [];

$matches = [];
$others = [];
$s = $conn->prepare("SELECT id, name, branch, year_of_study, teach_skill, learn_skill FROM users WHERE name <> ?");
if ($s) {
  $s->bind_param("s", $currentUser);
  $s->execute();
  $r = $s->get_result();
  if ($r) { $others = $r->fetch_all(MYSQLI_ASSOC); }
  $s->close();
}

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

layoutStart("Home", "Welcome back, " . $currentUser, "Here's what's happening across MIT CSN today.");
?>
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:16px;">
      <a href="profile.php" class="btn-outline" style="font-size:0.85rem; padding:7px 14px;">👤 Edit Profile</a>
      <a href="logout.php" class="btn-outline" style="font-size:0.85rem; padding:7px 14px; border-color:var(--danger); color:var(--danger);">🚪 Log Out</a>
    </div>
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
      <a class="stat-box" href="peer_sessions.php">
        <div class="stat-number"><?php echo count($peerSessions); ?></div>
        <div class="stat-label">Upcoming Peer Sessions</div>
      </a>
      <a class="stat-box" href="saved_announcements.php">
        <div class="stat-number"><?php echo $savedNoticeCount; ?></div>
        <div class="stat-label">Saved Notices</div>
      </a>
      <a class="stat-box" href="profile_view.php?id=<?php echo $me['id']; ?>">
        <div class="stat-number"><?php echo $avgRating ? $avgRating . ' ★' : '—'; ?></div>
        <div class="stat-label">Your rating (<?php echo $ratingRow['total']; ?> reviews)</div>
      </a>
    </div>

    <div class="dash-grid">
      <section>
        <div class="dash-section-head">
          <h2>🎓 Upcoming Peer Teaching Sessions</h2>
          <a href="peer_sessions.php">Explore or Host →</a>
        </div>
        <?php if (count($peerSessions) > 0) { ?>
          <div class="dash-stack" style="margin-bottom:28px;">
            <?php foreach ($peerSessions as $ps) { ?>
              <div class="announce-card <?php echo $ps['featured'] ? 'is-pinned' : ''; ?>">
                <div class="announce-top">
                  <span class="category-tag"><?php echo h($ps['skill_topic']); ?></span>
                  <span class="session-mode-tag <?php echo strtolower($ps['mode']) === 'online' ? 'online' : 'campus'; ?>">
                    <?php echo strtolower($ps['mode']) === 'online' ? '🌐 Online' : '🏫 Campus'; ?>
                  </span>
                  <span class="deadline-tag soon"><?php echo h(date("M j", strtotime($ps['session_date']))); ?> · <?php echo h($ps['session_time']); ?></span>
                </div>
                <h3><a href="peer_sessions.php"><?php echo h($ps['title']); ?></a></h3>
                <p class="announce-meta">Hosted by <strong><?php echo h($ps['host_name']); ?></strong> · <?php echo intval($ps['joined_count']); ?>/<?php echo intval($ps['max_seats']); ?> seats reserved</p>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="empty-state" style="margin:0 0 28px;"><h3>No upcoming sessions</h3><p><a href="peer_sessions.php" style="color:var(--accent); font-weight:600;">Host a peer teaching session</a> for your classmates!</p></div>
        <?php } ?>

        <div class="dash-section-head">
          <h2>📢 Latest Announcements</h2>
          <div>
            <a href="saved_announcements.php" style="margin-right:12px;">🔖 Saved (<?php echo $savedNoticeCount; ?>)</a>
            <a href="announcements.php">View all →</a>
          </div>
        </div>
        <?php if (count($announcements) > 0) { ?>
          <div class="dash-stack">
            <?php foreach ($announcements as $a) {
              $info = deadlineInfo($a['days_left']);
              $isSaved = isset($savedMap[$a['id']]);
            ?>
              <div class="announce-card <?php echo $a['pinned'] ? 'is-pinned' : ''; ?>">
                <div class="announce-top">
                  <span class="category-tag"><?php echo h($a['category']); ?></span>
                  <?php if ($a['pinned']) { ?><span class="pin-badge">📌 Pinned</span><?php } ?>
                  <?php if ($info) { ?><span class="deadline-tag <?php echo $info[1]; ?>"><?php echo h($info[0]); ?></span><?php } ?>
                </div>
                <h3><a href="announcements.php?category=<?php echo urlencode($a['category']); ?>"><?php echo h($a['title']); ?></a></h3>
                <p class="announce-body"><?php echo h(textLen($a['body']) > 140 ? textCut($a['body'], 140) . '…' : $a['body']); ?></p>
                <div class="card-actions" style="padding-top:4px;">
                  <span class="announce-meta">Posted <?php echo timeAgo($a['created_at']); ?></span>
                  <form class="ajax-form inline-form" action="toggle_save_announcement.php" method="POST" style="margin-left:auto;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                    <button type="submit" class="btn-outline <?php echo $isSaved ? 'active' : ''; ?>" style="padding:5px 11px; font-size:0.78rem;"><?php echo $isSaved ? '🔖 Saved' : '🔖 Save'; ?></button>
                  </form>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="empty-state" style="margin:0;"><h3>No announcements yet</h3><p>New notices from your college will show up here.</p></div>
        <?php } ?>
      </section>

      <aside>
        <div class="dash-section-head">
          <h2>✨ Suggested Matches</h2>
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
          <h2>🤝 Help Board</h2>
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
