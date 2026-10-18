<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

ensureSchema($conn);

$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : '';
$categories = announcementCategories();
$filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!in_array($filter, $categories, true)) { $filter = ''; }

// Seed default college announcements if table is empty so page never looks blank
$chkAnn = $conn->query("SELECT COUNT(*) AS c FROM announcements");
if ($chkAnn && intval($chkAnn->fetch_assoc()['c']) === 0) {
  $conn->query("INSERT INTO announcements (title, body, category, link, deadline, pinned) VALUES
    ('TCS Digital & Ninja Campus Recruitment Drive 2026', 'Final Year (BE/B.Tech all branches) students with 60%+ aggregate can register for the upcoming TCS Campus Drive. Aptitude + coding test in Main Auditorium.', 'Placements', 'https://nextstep.tcs.com', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1),
    ('Smart India Hackathon (SIH) Internal Shortlisting', 'Form teams of 6 students (at least 1 female member mandatory) and submit your problem statement presentation to the Innovation Cell.', 'Hackathons', 'https://sih.gov.in', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1),
    ('AICTE Pragati & Saksham Scholarship Applications Open', 'Eligible FY/SY students can submit their income and admission documents on the National Scholarship Portal before the deadline.', 'Scholarships', 'https://scholarships.gov.in', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 0),
    ('AWS Cloud & DevOps 2-Day Hands-On Workshop', 'Organized by Dept. of Computer Engineering & IT. Free AWS Educate credits and participation certificates for all attendees.', 'Workshops', '', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 0),
    ('Summer Research & Software Engineering Internship — DRDO / Persistent', 'TY and Final Year students interested in 8-week summer internships can apply via the Training & Placement Portal.', 'Internships', '', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 0)
  ");
}

$sql = "SELECT *, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE 1=1";
if ($filter !== '') {
  $f = $conn->real_escape_string($filter);
  $sql .= " AND category = '$f'";
}
if ($search !== '') {
  $s = $conn->real_escape_string($search);
  $sql .= " AND (title LIKE '%$s%' OR body LIKE '%$s%' OR category LIKE '%$s%')";
}
$sql .= " ORDER BY pinned DESC, created_at DESC, id DESC";
$res = $conn->query($sql);
$announcements = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$counts = [];
$cr = $conn->query("SELECT announcement_id, COUNT(*) AS c FROM announcement_interest GROUP BY announcement_id");
if ($cr) {
  while ($r = $cr->fetch_assoc()) { $counts[$r['announcement_id']] = $r['c']; }
}

$mine = [];
$savedMap = [];
if ($currentUser !== '') {
  $ms = $conn->prepare("SELECT announcement_id FROM announcement_interest WHERE user_name = ?");
  if ($ms) {
    $ms->bind_param("s", $currentUser);
    $ms->execute();
    $mr = $ms->get_result();
    if ($mr) { while ($r = $mr->fetch_assoc()) { $mine[$r['announcement_id']] = true; } }
    $ms->close();
  }

  $ss = $conn->prepare("SELECT announcement_id FROM saved_announcements WHERE user_name = ?");
  if ($ss) {
    $ss->bind_param("s", $currentUser);
    $ss->execute();
    $sr = $ss->get_result();
    if ($sr) { while ($r = $sr->fetch_assoc()) { $savedMap[$r['announcement_id']] = true; } }
    $ss->close();
  }
}

layoutStart("Announcements", "College Announcements", "Placements, internships, scholarships, exams, workshops, and hackathons at MIT CSN.");
?>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
      <form class="search-form" action="announcements.php" method="GET" style="margin:0; flex:1; min-width:260px;">
        <?php if ($filter !== '') { ?><input type="hidden" name="category" value="<?php echo h($filter); ?>"><?php } ?>
        <input type="text" name="search" placeholder="Search notices by company, exam, or keyword..." value="<?php echo h($search); ?>">
        <button type="submit">Search</button>
        <?php if ($search !== '') { ?><a href="announcements.php" class="btn-outline">Clear</a><?php } ?>
      </form>
      <a href="saved_announcements.php" class="btn-outline <?php echo count($savedMap) > 0 ? 'active' : ''; ?>">
        🔖 Saved Notices (<?php echo count($savedMap); ?>)
      </a>
    </div>

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
          $isSaved = isset($savedMap[$a['id']]);
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
            <p class="announce-meta">Posted <?php echo timeAgo($a['created_at']); ?><?php if ($a['deadline']) { ?> · Deadline: <?php echo h($a['deadline']); ?><?php } ?></p>
            <div class="card-actions">
              <?php if ($a['link']) { ?>
                <a class="connectBtn" href="<?php echo h($a['link']); ?>" target="_blank" rel="noopener noreferrer">View details ↗</a>
              <?php } ?>
              <?php if ($currentUser !== '') { ?>
                <form class="ajax-form inline-form" action="toggle_interest.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                  <button type="submit" class="btn-outline <?php echo $isMine ? 'active' : ''; ?>"><?php echo $isMine ? '✓ Interested' : 'Interested'; ?> · <?php echo $count; ?></button>
                </form>
                <form class="ajax-form inline-form" action="toggle_save_announcement.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                  <button type="submit" class="btn-outline <?php echo $isSaved ? 'active' : ''; ?>"><?php echo $isSaved ? '🔖 Saved' : '🔖 Save'; ?></button>
                </form>
              <?php } else { ?>
                <a href="login.php" class="btn-outline">Log in to Save / Mark Interest (<?php echo $count; ?>)</a>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3>No announcements found</h3>
        <p><?php echo $filter !== '' ? "Nothing posted under " . h($filter) . " right now." : "Check back soon for new college notices."; ?></p>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
