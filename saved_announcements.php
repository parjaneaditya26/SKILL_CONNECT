<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

ensureSchema($conn);

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Saved Announcements", "Log in to view and manage your bookmarked college announcements.");
}

$currentUser = $_SESSION['profileName'];
$categories = announcementCategories();
$filter = isset($_GET['category']) ? $_GET['category'] : '';
if (!in_array($filter, $categories, true)) { $filter = ''; }

$savedList = [];
if ($filter !== '') {
  $stmt = $conn->prepare("SELECT a.*, s.created_at AS saved_at, DATEDIFF(a.deadline, CURDATE()) AS days_left
                          FROM saved_announcements s
                          INNER JOIN announcements a ON a.id = s.announcement_id
                          WHERE s.user_name = ? AND a.category = ?
                          ORDER BY a.pinned DESC, s.created_at DESC");
  if ($stmt) {
    $stmt->bind_param("ss", $currentUser, $filter);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) { $savedList = $res->fetch_all(MYSQLI_ASSOC); }
    $stmt->close();
  }
} else {
  $stmt = $conn->prepare("SELECT a.*, s.created_at AS saved_at, DATEDIFF(a.deadline, CURDATE()) AS days_left
                          FROM saved_announcements s
                          INNER JOIN announcements a ON a.id = s.announcement_id
                          WHERE s.user_name = ?
                          ORDER BY a.pinned DESC, s.created_at DESC");
  if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) { $savedList = $res->fetch_all(MYSQLI_ASSOC); }
    $stmt->close();
  }
}

$counts = [];
$cr = $conn->query("SELECT announcement_id, COUNT(*) AS c FROM announcement_interest GROUP BY announcement_id");
if ($cr) {
  while ($r = $cr->fetch_assoc()) { $counts[$r['announcement_id']] = $r['c']; }
}

$mine = [];
$ms = $conn->prepare("SELECT announcement_id FROM announcement_interest WHERE user_name = ?");
if ($ms) {
  $ms->bind_param("s", $currentUser);
  $ms->execute();
  $mr = $ms->get_result();
  if ($mr) { while ($r = $mr->fetch_assoc()) { $mine[$r['announcement_id']] = true; } }
  $ms->close();
}

layoutStart("Saved Announcements", "Saved Announcements", "Quick access to all placements, internships, hackathons, and college notices you've bookmarked.");
?>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
      <div class="chips" style="margin-bottom:0;">
        <a href="saved_announcements.php" class="chip <?php echo $filter === '' ? 'active' : ''; ?>">All Saved (<?php echo count($savedList); ?>)</a>
        <?php foreach ($categories as $cat) { ?>
          <a href="saved_announcements.php?category=<?php echo urlencode($cat); ?>" class="chip <?php echo $filter === $cat ? 'active' : ''; ?>"><?php echo h($cat); ?></a>
        <?php } ?>
      </div>
      <a href="announcements.php" class="btn-outline">← Back to All Announcements</a>
    </div>

    <?php if (count($savedList) > 0) { ?>
      <div class="announce-list">
        <?php foreach ($savedList as $a) {
          $info = deadlineInfo($a['days_left']);
          $closed = ($info && $info[1] === 'closed');
          $isMine = isset($mine[$a['id']]);
          $count = isset($counts[$a['id']]) ? $counts[$a['id']] : 0;
        ?>
          <div class="announce-card <?php echo $a['pinned'] ? 'is-pinned' : ''; ?> <?php echo $closed ? 'is-closed' : ''; ?>">
            <div class="announce-top">
              <span class="category-tag"><?php echo h($a['category']); ?></span>
              <?php if ($a['pinned']) { ?><span class="pin-badge">📌 Pinned</span><?php } ?>
              <?php if ($info) { ?><span class="deadline-tag <?php echo $info[1]; ?>"><?php echo h($info[0]); ?></span><?php } ?>
              <span class="saved-badge">🔖 Saved <?php echo timeAgo($a['saved_at']); ?></span>
            </div>
            <h3><?php echo h($a['title']); ?></h3>
            <p class="announce-body"><?php echo nl2br(h($a['body'])); ?></p>
            <p class="announce-meta">Posted <?php echo timeAgo($a['created_at']); ?><?php if ($a['deadline']) { ?> · Deadline: <?php echo h($a['deadline']); ?><?php } ?></p>
            <div class="card-actions">
              <?php if ($a['link']) { ?>
                <a class="connectBtn" href="<?php echo h($a['link']); ?>" target="_blank" rel="noopener noreferrer">View details ↗</a>
              <?php } ?>
              <form class="ajax-form inline-form" action="toggle_interest.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <button type="submit" class="btn-outline <?php echo $isMine ? 'active' : ''; ?>"><?php echo $isMine ? '✓ Interested' : 'Interested'; ?> · <?php echo $count; ?></button>
              </form>
              <form class="ajax-form inline-form" action="toggle_save_announcement.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <button type="submit" class="btn-outline active" title="Remove from Saved">🔖 Saved (Remove)</button>
              </form>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3>No saved announcements yet</h3>
        <p><?php echo $filter !== '' ? "You haven't bookmarked any notices under " . h($filter) . "." : "Click '🔖 Save' on any college notice to bookmark it here for quick reference."; ?></p>
        <a href="announcements.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Browse Announcements</a>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
