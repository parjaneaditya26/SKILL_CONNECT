<?php
require_once __DIR__ . '/session_init.php';
if (empty($_SESSION["isAdmin"])) {
  header("Location: login.php");
  exit;
}

include 'db_connect.php';
include 'helpers.php';

ensureSchema($conn);

// Fetch all core datasets safely
$usersRes = $conn->query("SELECT * FROM users ORDER BY id DESC");
$usersList = $usersRes ? $usersRes->fetch_all(MYSQLI_ASSOC) : [];

$requestsRes = $conn->query("SELECT * FROM requests ORDER BY id DESC");
$requestsList = $requestsRes ? $requestsRes->fetch_all(MYSQLI_ASSOC) : [];

$reportsRes = $conn->query("SELECT * FROM reports ORDER BY id DESC");
$reportsList = $reportsRes ? $reportsRes->fetch_all(MYSQLI_ASSOC) : [];

$annRes = $conn->query("SELECT a.*, DATEDIFF(a.deadline, CURDATE()) AS days_left,
  (SELECT COUNT(*) FROM announcement_interest i WHERE i.announcement_id = a.id) AS interested_count,
  (SELECT COUNT(*) FROM saved_announcements sa WHERE sa.announcement_id = a.id) AS saved_count
  FROM announcements a ORDER BY a.pinned DESC, a.created_at DESC");
$annList = $annRes ? $annRes->fetch_all(MYSQLI_ASSOC) : [];

$sessionsRes = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM peer_session_participants p WHERE p.session_id = s.id) AS joined_count FROM peer_sessions s ORDER BY s.featured DESC, s.session_date ASC");
$sessionsList = $sessionsRes ? $sessionsRes->fetch_all(MYSQLI_ASSOC) : [];

$helpRes = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM help_responses r WHERE r.post_id = p.id) AS resp_count FROM help_posts p ORDER BY p.created_at DESC");
$helpList = $helpRes ? $helpRes->fetch_all(MYSQLI_ASSOC) : [];

$ratingRow = ['avg_r' => null, 'cnt' => 0];
$rRes = $conn->query("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM ratings");
if ($rRes) { $ratingRow = $rRes->fetch_assoc(); }

// Compute Analytics Breakdown
$totalUsers = count($usersList);
$verifiedUsers = 0;
$branchCounts = [];
$yearCounts = ['FY' => 0, 'SY' => 0, 'TY' => 0, 'Final Year' => 0];
$teachSkillFreq = [];
$learnSkillFreq = [];

foreach ($usersList as $u) {
  if (!empty($u['verified'])) { $verifiedUsers++; }
  $b = trim($u['branch'] ?? '') ?: 'Other';
  $branchCounts[$b] = ($branchCounts[$b] ?? 0) + 1;

  $y = trim($u['year_of_study'] ?? '');
  if (isset($yearCounts[$y])) { $yearCounts[$y]++; }

  if (!empty($u['teach_skill']) && strtolower($u['teach_skill']) !== 'nothing yet') {
    foreach (explode(',', $u['teach_skill']) as $sk) {
      $skClean = ucwords(strtolower(trim($sk)));
      if ($skClean !== '') { $teachSkillFreq[$skClean] = ($teachSkillFreq[$skClean] ?? 0) + 1; }
    }
  }
  if (!empty($u['learn_skill']) && strtolower($u['learn_skill']) !== 'nothing yet') {
    foreach (explode(',', $u['learn_skill']) as $sk) {
      $skClean = ucwords(strtolower(trim($sk)));
      if ($skClean !== '') { $learnSkillFreq[$skClean] = ($learnSkillFreq[$skClean] ?? 0) + 1; }
    }
  }
}
arsort($branchCounts);
arsort($teachSkillFreq);
arsort($learnSkillFreq);

$acceptedReqCount = 0;
$pendingReqCount = 0;
foreach ($requestsList as $r) {
  if ($r['status'] === 'accepted') $acceptedReqCount++;
  elseif ($r['status'] === 'pending') $pendingReqCount++;
}

$upcomingSessionCount = 0;
$totalSessionRsvps = 0;
foreach ($sessionsList as $s) {
  if ($s['status'] === 'upcoming') $upcomingSessionCount++;
  $totalSessionRsvps += intval($s['joined_count']);
}

$openHelpCount = 0;
$totalHelpResponses = 0;
foreach ($helpList as $hp) {
  if ($hp['status'] === 'open') $openHelpCount++;
  $totalHelpResponses += intval($hp['resp_count']);
}

$verifiedPct = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0;

layoutStart("Admin Control Center", "Admin Control Center", "Comprehensive campus analytics, student management, peer teaching sessions, announcements, and moderation.", "admin");
?>
    <!-- Quick Actions Toolbar -->
    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <a href="admin_announcements.php" class="connectBtn" style="text-decoration:none;">📢 + Post Announcement</a>
        <a href="#sessions-section" class="btn-outline" onclick="var d=document.getElementById('adminHostSessionDetails'); if(d){d.open=true; setTimeout(function(){var t=document.getElementById('admin_session_title'); if(t)t.focus();},100);}">🎓 + Schedule Peer Session</a>
      </div>
      <div class="admin-toolbar-right">
        <a href="export_users.php" class="btn-outline">⬇ Export Users CSV</a>
        <a href="export_requests.php" class="btn-outline">⬇ Export Requests CSV</a>
      </div>
    </div>

    <!-- 6-Card Executive KPI Grid -->
    <div class="admin-kpi-grid">
      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">Registered Students</span>
          <span class="admin-kpi-icon">👥</span>
        </div>
        <div class="stat-number"><?php echo $totalUsers; ?></div>
        <div class="admin-kpi-sub">
          <span class="kpi-pill good">✓ <?php echo $verifiedUsers; ?> Verified (<?php echo $verifiedPct; ?>%)</span>
          <span><?php echo max(0, $totalUsers - $verifiedUsers); ?> Pending</span>
        </div>
      </div>

      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">Skill Connections</span>
          <span class="admin-kpi-icon">🤝</span>
        </div>
        <div class="stat-number"><?php echo $acceptedReqCount; ?></div>
        <div class="admin-kpi-sub">
          <span class="kpi-pill warn"><?php echo $pendingReqCount; ?> Pending</span>
          <span><?php echo count($requestsList); ?> Total Requests</span>
        </div>
      </div>

      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">Peer Teaching Sessions</span>
          <span class="admin-kpi-icon">🎓</span>
        </div>
        <div class="stat-number"><?php echo $upcomingSessionCount; ?></div>
        <div class="admin-kpi-sub">
          <span class="kpi-pill good"><?php echo $totalSessionRsvps; ?> RSVPs</span>
          <span><?php echo count($sessionsList); ?> Total Sessions</span>
        </div>
      </div>

      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">College Announcements</span>
          <span class="admin-kpi-icon">📢</span>
        </div>
        <div class="stat-number"><?php echo count($annList); ?></div>
        <div class="admin-kpi-sub">
          <?php
          $pinnedCnt = 0; $savedTotal = 0;
          foreach ($annList as $a) { if ($a['pinned']) $pinnedCnt++; $savedTotal += intval($a['saved_count']); }
          ?>
          <span class="kpi-pill">📌 <?php echo $pinnedCnt; ?> Pinned</span>
          <span>🔖 <?php echo $savedTotal; ?> Saved</span>
        </div>
      </div>

      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">Help Board Activity</span>
          <span class="admin-kpi-icon">🆘</span>
        </div>
        <div class="stat-number"><?php echo $openHelpCount; ?></div>
        <div class="admin-kpi-sub">
          <span class="kpi-pill good"><?php echo $totalHelpResponses; ?> Replies</span>
          <span><?php echo count($helpList); ?> Total Posts</span>
        </div>
      </div>

      <div class="admin-kpi-card">
        <div class="admin-kpi-top">
          <span class="admin-kpi-label">Trust &amp; Moderation</span>
          <span class="admin-kpi-icon">⭐</span>
        </div>
        <div class="stat-number"><?php echo $ratingRow['avg_r'] ? round($ratingRow['avg_r'], 1) . ' ★' : '5.0 ★'; ?></div>
        <div class="admin-kpi-sub">
          <span class="kpi-pill <?php echo count($reportsList) > 0 ? 'danger' : 'good'; ?>">
            🚩 <?php echo count($reportsList); ?> Reports
          </span>
          <span><?php echo intval($ratingRow['cnt']); ?> Reviews</span>
        </div>
      </div>
    </div>

    <!-- Visual Analytics & Campus Insights Grid (3 Columns) -->
    <div class="admin-analytics-grid">
      <!-- Column 1: Branch Distribution -->
      <div class="admin-insight-card">
        <div class="admin-insight-head">
          <h3>📊 Students by Branch</h3>
          <span class="announce-meta"><?php echo $totalUsers; ?> total</span>
        </div>
        <?php if (count($branchCounts) > 0) { ?>
          <div class="branch-bars">
            <?php foreach ($branchCounts as $bName => $bCount) {
              $pct = $totalUsers > 0 ? round(($bCount / $totalUsers) * 100) : 0;
            ?>
              <div class="branch-bar-item">
                <div class="branch-bar-label">
                  <span><?php echo h($bName); ?></span>
                  <strong><?php echo $bCount; ?> (<?php echo $pct; ?>%)</strong>
                </div>
                <div class="seat-meter-bar">
                  <div class="seat-meter-fill" style="width: <?php echo max(6, $pct); ?>%;"></div>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <p class="announce-meta">No student profiles registered yet.</p>
        <?php } ?>
      </div>

      <!-- Column 2: Skills Supply vs Demand & Year Breakdown -->
      <div class="admin-insight-card">
        <div class="admin-insight-head">
          <h3>🔥 Top Campus Skills</h3>
          <span class="announce-meta">Supply vs. Demand</span>
        </div>
        <p class="insight-sublabel">Most Taught by Mentors:</p>
        <div class="skill-tags-row" style="margin-bottom:12px;">
          <?php if (count($teachSkillFreq) > 0) {
            foreach (array_slice($teachSkillFreq, 0, 8, true) as $sk => $cnt) {
              echo '<span class="skill-tag">' . h($sk) . ' <strong>(' . $cnt . ')</strong></span>';
            }
          } else { echo '<span class="skill-tag skill-tag-empty">No skills listed yet</span>'; } ?>
        </div>

        <p class="insight-sublabel">Most Requested to Learn:</p>
        <div class="skill-tags-row" style="margin-bottom:14px;">
          <?php if (count($learnSkillFreq) > 0) {
            foreach (array_slice($learnSkillFreq, 0, 8, true) as $sk => $cnt) {
              echo '<span class="skill-tag">' . h($sk) . ' <strong>(' . $cnt . ')</strong></span>';
            }
          } else { echo '<span class="skill-tag skill-tag-empty">No learning goals yet</span>'; } ?>
        </div>

        <p class="insight-sublabel">Academic Year Breakdown:</p>
        <div class="year-pills-row">
          <?php foreach ($yearCounts as $yr => $yc) { ?>
            <div class="year-mini-box">
              <strong><?php echo $yc; ?></strong>
              <span><?php echo h($yr); ?></span>
            </div>
          <?php } ?>
        </div>
      </div>

      <!-- Column 3: Recent Campus Activity Stream -->
      <div class="admin-insight-card">
        <div class="admin-insight-head">
          <h3>⚡ Recent Platform Pulse</h3>
          <span class="announce-meta">Live Feed</span>
        </div>
        <div class="activity-feed">
          <?php foreach (array_slice($usersList, 0, 2) as $u) { ?>
            <div class="activity-item">
              <span class="activity-dot user">👤</span>
              <div>
                <strong><?php echo h($u['name']); ?></strong> joined from <?php echo h($u['branch']); ?>
                <div class="announce-meta"><?php echo timeAgo($u['created_at'] ?? ''); ?></div>
              </div>
            </div>
          <?php } ?>
          <?php foreach (array_slice($sessionsList, 0, 2) as $s) { ?>
            <div class="activity-item">
              <span class="activity-dot session">🎓</span>
              <div>
                <strong><?php echo h($s['host_name']); ?></strong> scheduled <em><?php echo h($s['title']); ?></em>
                <div class="announce-meta"><?php echo h($s['session_date']); ?> · <?php echo intval($s['joined_count']); ?> RSVPs</div>
              </div>
            </div>
          <?php } ?>
          <?php foreach (array_slice($annList, 0, 2) as $a) { ?>
            <div class="activity-item">
              <span class="activity-dot announce">📢</span>
              <div>
                Notice posted: <strong><?php echo h($a['title']); ?></strong>
                <div class="announce-meta"><?php echo h($a['category']); ?> · <?php echo intval($a['interested_count']); ?> interested</div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>

    <!-- Interactive Tabbed Filter & Live Search Bar -->
    <div class="admin-console-bar">
      <div class="admin-tabs-row">
        <button type="button" class="admin-tab-btn active" data-target="all">All Sections</button>
        <button type="button" class="admin-tab-btn" data-target="users-section">👥 Users (<?php echo $totalUsers; ?>)</button>
        <button type="button" class="admin-tab-btn" data-target="sessions-section">🎓 Peer Sessions (<?php echo count($sessionsList); ?>)</button>
        <button type="button" class="admin-tab-btn" data-target="announcements-section">📢 Notices (<?php echo count($annList); ?>)</button>
        <button type="button" class="admin-tab-btn" data-target="help-section">🤝 Help Board (<?php echo count($helpList); ?>)</button>
        <button type="button" class="admin-tab-btn" data-target="requests-section">🔄 Connections (<?php echo count($requestsList); ?>)</button>
        <button type="button" class="admin-tab-btn" data-target="reports-section">🚩 Reports (<?php echo count($reportsList); ?>)</button>
      </div>
      <div class="admin-search-box">
        <input type="text" id="adminSearchInput" placeholder="🔍 Quick filter any table row by name, skill, branch, status...">
      </div>
    </div>

    <!-- 1. Users Management Section -->
    <section id="users-section" class="admin-section-panel">
      <div class="dash-section-head">
        <h2>👥 Registered Students &amp; Mentors (<?php echo $totalUsers; ?>)</h2>
        <a href="export_users.php">⬇ Download CSV</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Student</th><th>Branch &amp; Year</th><th>Teaches</th><th>Wants to Learn</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if ($totalUsers > 0) { ?>
              <?php foreach ($usersList as $u) { ?>
                <tr class="filterable-row">
                  <td>#<?php echo $u['id']; ?></td>
                  <td>
                    <strong><a href="profile_view.php?id=<?php echo $u['id']; ?>" style="color:var(--text-dark);"><?php echo h($u['name']); ?></a></strong>
                    <div class="announce-meta"><?php echo h($u['email']); ?> · <?php echo h($u['mobile']); ?></div>
                  </td>
                  <td>
                    <span class="category-tag"><?php echo h($u['branch']); ?></span>
                    <span class="announce-meta"><?php echo h($u['year_of_study']); ?></span>
                  </td>
                  <td><?php echo skillTags($u['teach_skill']); ?></td>
                  <td><?php echo skillTags($u['learn_skill']); ?></td>
                  <td>
                    <?php if ($u['verified']) { ?>
                      <span class="status-pill accepted">✓ Verified</span>
                    <?php } else { ?>
                      <span class="status-pill pending">Unverified</span>
                    <?php } ?>
                  </td>
                  <td class="action-cell">

                    <form action="admin_delete_user.php" method="POST" class="inline-form" onsubmit="return confirm('Delete this user?');">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                      <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">No student profiles registered yet.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 2. Peer Teaching Sessions Management Section -->
    <section id="sessions-section" class="admin-section-panel">
      <div class="dash-section-head" style="margin-top:28px;">
        <h2>🎓 Peer Teaching Sessions (<?php echo count($sessionsList); ?>)</h2>
        <div style="display:flex; gap:12px; align-items:center;">
          <?php if (count($sessionsList) > 0) { ?>
            <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="delete_all">
              <button type="submit" class="btn-danger" style="padding:5px 12px; font-size:0.78rem;">🗑️ Delete All Sessions</button>
            </form>
          <?php } ?>
          <a href="peer_sessions.php">Open Peer Sessions Page →</a>
        </div>
      </div>

      <details class="new-post" id="adminHostSessionDetails" style="margin-bottom:16px;">
        <summary class="connectBtn">＋ Host / Schedule a Peer Teaching Session (as Admin)</summary>
        <form class="ajax-form" action="save_peer_session.php" method="POST" style="max-width:780px; margin:14px 0 0;">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="from_admin" value="1">
          <div class="form-grid-2">
            <div class="form-group">
              <label for="admin_session_title">Session Title</label>
              <input type="text" id="admin_session_title" name="title" maxlength="180" required placeholder="e.g. Campus Git & GitHub Bootcamp">
            </div>
            <div class="form-group">
              <label for="admin_skill_topic">Skill / Topic</label>
              <input type="text" id="admin_skill_topic" name="skill_topic" maxlength="100" required placeholder="e.g. Web Development, Python, DSA">
            </div>
            <div class="form-group">
              <label for="admin_session_date">Date</label>
              <input type="date" id="admin_session_date" name="session_date" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" required>
            </div>
            <div class="form-group">
              <label for="admin_session_time">Start Time</label>
              <input type="text" id="admin_session_time" name="session_time" maxlength="30" required placeholder="e.g. 4:30 PM">
            </div>
            <div class="form-group">
              <label for="admin_duration_mins">Duration</label>
              <select id="admin_duration_mins" name="duration_mins">
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60" selected>60 minutes (1 hour)</option>
                <option value="90">90 minutes (1.5 hours)</option>
                <option value="120">120 minutes (2 hours)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="admin_mode">Session Mode</label>
              <select id="admin_mode" name="mode">
                <option value="Online">🌐 Online (Google Meet / Zoom)</option>
                <option value="Campus">🏫 On-Campus (Lab / Classroom)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="admin_location_link">Meeting Link or Room Location</label>
              <input type="text" id="admin_location_link" name="location_link" maxlength="400" required placeholder="e.g. https://meet.google.com/... or CSE Lab 2">
            </div>
            <div class="form-group">
              <label for="admin_max_seats">Max Seats</label>
              <input type="number" id="admin_max_seats" name="max_seats" min="2" max="200" value="30" required>
            </div>
          </div>
          <div class="form-group">
            <label for="admin_description">Agenda &amp; Prerequisites</label>
            <textarea id="admin_description" name="description" maxlength="1500" required placeholder="Describe what topics will be covered and who should attend..."></textarea>
          </div>
          <button type="submit">Schedule Peer Session</button>
        </form>
      </details>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Title &amp; Topic</th><th>Host Mentor</th><th>Schedule</th><th>Mode &amp; Venue</th><th>RSVPs</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (count($sessionsList) > 0) { ?>
              <?php foreach ($sessionsList as $s) { ?>
                <tr class="filterable-row">
                  <td>
                    <?php if ($s['featured']) { ?><span class="pin-badge" style="margin-right:4px;">⭐ Featured</span><?php } ?>
                    <strong><?php echo h($s['title']); ?></strong>
                    <div class="announce-meta">Topic: <?php echo h($s['skill_topic']); ?></div>
                  </td>
                  <td><?php echo h($s['host_name']); ?></td>
                  <td><?php echo h($s['session_date']); ?> · <?php echo h($s['session_time']); ?> (<?php echo intval($s['duration_mins']); ?>m)</td>
                  <td><span class="category-tag"><?php echo h($s['mode']); ?></span> <span class="announce-meta"><?php echo h(textCut($s['location_link'], 35)); ?></span></td>
                  <td><strong><?php echo intval($s['joined_count']); ?></strong> / <?php echo intval($s['max_seats']); ?></td>
                  <td><span class="status-pill <?php echo $s['status'] === 'upcoming' ? 'accepted' : 'pending'; ?>"><?php echo ucfirst(h($s['status'])); ?></span></td>
                  <td class="action-cell">
                    <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                      <input type="hidden" name="action" value="feature">
                      <button type="submit" class="admin-mini-btn"><?php echo $s['featured'] ? 'Unfeature' : '⭐ Feature'; ?></button>
                    </form>
                    <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                      <input type="hidden" name="action" value="<?php echo $s['status'] === 'upcoming' ? 'complete' : 'reopen'; ?>">
                      <button type="submit" class="admin-mini-btn"><?php echo $s['status'] === 'upcoming' ? 'Complete' : 'Reopen'; ?></button>
                    </form>
                    <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">No peer teaching sessions scheduled yet.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 3. Announcements Quick Management Section -->
    <section id="announcements-section" class="admin-section-panel">
      <div class="dash-section-head" style="margin-top:28px;">
        <h2>📢 College Announcements (<?php echo count($annList); ?>)</h2>
        <a href="admin_announcements.php">＋ Post / Edit Announcements →</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Title</th><th>Category</th><th>Deadline</th><th>Engagement</th><th>Posted</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (count($annList) > 0) { ?>
              <?php foreach ($annList as $a) { $info = deadlineInfo($a['days_left']); ?>
                <tr class="filterable-row">
                  <td><?php echo $a['pinned'] ? '📌 ' : ''; ?><strong><?php echo h($a['title']); ?></strong></td>
                  <td><span class="category-tag"><?php echo h($a['category']); ?></span></td>
                  <td><?php echo $a['deadline'] ? h($a['deadline']) . ($info ? ' (' . h($info[0]) . ')' : '') : '—'; ?></td>
                  <td>👍 <?php echo intval($a['interested_count']); ?> · 🔖 <?php echo intval($a['saved_count']); ?></td>
                  <td><?php echo timeAgo($a['created_at']); ?></td>
                  <td class="action-cell">
                    <form class="ajax-form inline-form" action="admin_announcement_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                      <input type="hidden" name="action" value="<?php echo $a['pinned'] ? 'unpin' : 'pin'; ?>">
                      <button type="submit" class="admin-mini-btn"><?php echo $a['pinned'] ? 'Unpin' : '📌 Pin'; ?></button>
                    </form>
                    <form class="ajax-form inline-form" action="admin_announcement_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">No announcements posted yet.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 4. Help Board Moderation Section -->
    <section id="help-section" class="admin-section-panel">
      <div class="dash-section-head" style="margin-top:28px;">
        <h2>🤝 Help Board Posts (<?php echo count($helpList); ?>)</h2>
        <a href="help_board.php">View Help Board →</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Title</th><th>Author</th><th>Type</th><th>Responses</th><th>Status</th><th>Posted</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (count($helpList) > 0) { ?>
              <?php foreach ($helpList as $hp) { ?>
                <tr class="filterable-row">
                  <td>
                    <strong><?php echo h($hp['title']); ?></strong>
                    <div class="announce-meta"><?php echo h(textCut($hp['details'], 80)); ?></div>
                  </td>
                  <td><?php echo h($hp['author_name']); ?></td>
                  <td><span class="category-tag"><?php echo h($hp['type']); ?></span></td>
                  <td><?php echo intval($hp['resp_count']); ?></td>
                  <td><span class="status-pill <?php echo $hp['status'] === 'open' ? 'accepted' : 'pending'; ?>"><?php echo ucfirst(h($hp['status'])); ?></span></td>
                  <td><?php echo timeAgo($hp['created_at']); ?></td>
                  <td class="action-cell">
                    <form class="ajax-form inline-form" action="admin_user_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $hp['id']; ?>">
                      <input type="hidden" name="action" value="toggle_help_post">
                      <button type="submit" class="admin-mini-btn"><?php echo $hp['status'] === 'open' ? 'Close' : 'Reopen'; ?></button>
                    </form>
                    <form class="ajax-form inline-form" action="admin_user_action.php" method="POST">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $hp['id']; ?>">
                      <input type="hidden" name="action" value="delete_help_post">
                      <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">No Help Board posts yet.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 5. Connection Requests Section -->
    <section id="requests-section" class="admin-section-panel">
      <div class="dash-section-head" style="margin-top:28px;">
        <h2>🔄 Skill Swap Connection Requests (<?php echo count($requestsList); ?>)</h2>
        <a href="export_requests.php">⬇ Download CSV</a>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Requester</th><th>Target Mentor</th><th>Status</th><th>Date</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (count($requestsList) > 0) { ?>
              <?php foreach ($requestsList as $r) { ?>
                <tr class="filterable-row">
                  <td>#<?php echo $r['id']; ?></td>
                  <td><strong><?php echo h($r['requester_name']); ?></strong></td>
                  <td><strong><?php echo h($r['target_name']); ?></strong></td>
                  <td><span class="status-pill <?php echo h($r['status']); ?>"><?php echo ucfirst(h($r['status'])); ?></span></td>
                  <td><?php echo h($r['created_at']); ?></td>
                  <td>
                    <form action="admin_delete_request.php" method="POST" class="inline-form" onsubmit="return confirm('Delete this connection request?');">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                      <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">No connection requests recorded yet.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 6. Reports & Trust Section -->
    <section id="reports-section" class="admin-section-panel">
      <div class="dash-section-head" style="margin-top:28px;">
        <h2>🚩 User Reports &amp; Safety Queue (<?php echo count($reportsList); ?>)</h2>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Reporter</th><th>Reported Profile</th><th>Reason</th><th>Details</th><th>Date</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (count($reportsList) > 0) { ?>
              <?php foreach ($reportsList as $rp) { ?>
                <tr class="filterable-row">
                  <td>#<?php echo $rp['id']; ?></td>
                  <td><?php echo h($rp['reporter_name']); ?></td>
                  <td><strong><?php echo h($rp['target_name']); ?></strong></td>
                  <td><span class="status-pill declined"><?php echo h($rp['reason']); ?></span></td>
                  <td><?php echo h($rp['details']); ?></td>
                  <td><?php echo h($rp['created_at']); ?></td>
                  <td>
                    <form action="admin_delete_report.php" method="POST" class="inline-form" onsubmit="return confirm('Dismiss this report?');">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="id" value="<?php echo $rp['id']; ?>">
                      <button type="submit" class="admin-delete-btn">Dismiss</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="7" style="text-align:center; padding:24px; color:#3f6b37; font-weight:600;">✓ All clear! Zero active user reports on campus.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>
<?php layoutEnd('admin'); ?>