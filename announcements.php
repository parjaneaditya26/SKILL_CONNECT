<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Announcements", "Log in to see college announcements.");
}

$currentUser = $_SESSION['profileName'];
$categories = announcementCategories();
$filter = isset($_GET['category']) ? $_GET['category'] : '';
if (!in_array($filter, $categories, true)) { $filter = ''; }

if ($filter !== '') {
  $stmt = $conn->prepare("SELECT *, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE category = ? ORDER BY pinned DESC, created_at DESC, id DESC");
  $stmt->bind_param("s", $filter);
} else {
  $stmt = $conn->prepare("SELECT *, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements ORDER BY pinned DESC, created_at DESC, id DESC");
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = [];
$cr = $conn->query("SELECT announcement_id, COUNT(*) AS c FROM announcement_interest GROUP BY announcement_id");
while ($r = $cr->fetch_assoc()) { $counts[$r['announcement_id']] = $r['c']; }

$mine = [];
$ms = $conn->prepare("SELECT announcement_id FROM announcement_interest WHERE user_name = ?");
$ms->bind_param("s", $currentUser);
$ms->execute();
$mr = $ms->get_result();
while ($r = $mr->fetch_assoc()) { $mine[$r['announcement_id']] = true; }
$ms->close();

layoutStart("Announcements", "Announcements", "Placements, internships, scholarships, exams and events from your college.");
?>
    <div class="chips">
      <a href="announcements.php" class="chip <?php echo $filter === '' ? 'active' : ''; ?>">All</a>
      <?php foreach ($categories as $cat) { ?>
        <a href="announcements.php?category=<?php echo urlencode($cat); ?>" class="chip <?php echo $filter === $cat ? 'active' : ''; ?>"><?php echo h($cat); ?></a>
      <?php } ?>
    </div>

    <?php if (count($announcements) > 0) { ?>
      <div class="announce-list">
        <?php foreach ($announcements as $a) {
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
            </div>
            <h3><?php echo h($a['title']); ?></h3>
            <p class="announce-body"><?php echo nl2br(h($a['body'])); ?></p>
            <p class="announce-meta">Posted <?php echo timeAgo($a['created_at']); ?></p>
            <div class="card-actions">
              <?php if ($a['link']) { ?>
                <a class="connectBtn" href="<?php echo h($a['link']); ?>" target="_blank" rel="noopener noreferrer">View details</a>
              <?php } ?>
              <form class="ajax-form inline-form" action="toggle_interest.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <button type="submit" class="btn-outline <?php echo $isMine ? 'active' : ''; ?>"><?php echo $isMine ? '✓ Interested' : 'Interested'; ?> · <?php echo $count; ?></button>
              </form>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3>No announcements yet</h3>
        <p><?php echo $filter !== '' ? "Nothing posted under " . h($filter) . " right now." : "Check back soon for new notices."; ?></p>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
