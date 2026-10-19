<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

ensureSchema($conn);

$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : '';
$isAdmin = !empty($_SESSION['isAdmin']);

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$validFilters = ['upcoming', 'joined', 'hosted', 'completed', 'all'];
if (!in_array($filter, $validFilters, true)) { $filter = 'upcoming'; }

$sql = "SELECT s.*, u.id AS host_id, u.branch AS host_branch, u.year_of_study AS host_year,
        (SELECT COUNT(*) FROM peer_session_participants p WHERE p.session_id = s.id) AS joined_count
        FROM peer_sessions s
        LEFT JOIN users u ON u.name = s.host_name
        WHERE 1=1";

if ($filter === 'upcoming') {
  $sql .= " AND s.status = 'upcoming'";
} elseif ($filter === 'completed') {
  $sql .= " AND s.status = 'completed'";
} elseif ($filter === 'hosted' && $currentUser !== '') {
  $cu = $conn->real_escape_string($currentUser);
  $sql .= " AND s.host_name = '$cu'";
} elseif ($filter === 'joined' && $currentUser !== '') {
  $cu = $conn->real_escape_string($currentUser);
  $sql .= " AND s.id IN (SELECT session_id FROM peer_session_participants WHERE user_name = '$cu')";
}

if ($search !== '') {
  $sc = $conn->real_escape_string($search);
  $sql .= " AND (s.title LIKE '%$sc%' OR s.skill_topic LIKE '%$sc%' OR s.host_name LIKE '%$sc%' OR s.description LIKE '%$sc%')";
}

$sql .= " ORDER BY s.featured DESC, (s.status = 'upcoming') DESC, s.session_date ASC, s.id DESC";
$res = $conn->query($sql);
$sessions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$myJoined = [];
if ($currentUser !== '') {
  $stmtJ = $conn->prepare("SELECT session_id FROM peer_session_participants WHERE user_name = ?");
  if ($stmtJ) {
    $stmtJ->bind_param("s", $currentUser);
    $stmtJ->execute();
    $rj = $stmtJ->get_result();
    if ($rj) {
      while ($row = $rj->fetch_assoc()) { $myJoined[$row['session_id']] = true; }
    }
    $stmtJ->close();
  }
}

$totalUpcoming = 0;
$totalRsvps = 0;
$r1 = $conn->query("SELECT COUNT(*) AS c FROM peer_sessions WHERE status = 'upcoming'");
if ($r1) { $totalUpcoming = intval($r1->fetch_assoc()['c']); }
$r2 = $conn->query("SELECT COUNT(*) AS c FROM peer_session_participants");
if ($r2) { $totalRsvps = intval($r2->fetch_assoc()['c']); }

$navMode = ($isAdmin && $currentUser === '') ? 'admin' : 'user';
layoutStart("Peer Teaching Sessions", "Peer Teaching Sessions", "Join live student-led workshops, study circles, and hands-on skill sessions—or host your own!", $navMode);
?>
    <div class="dash-stats" style="margin-bottom:22px;">
      <div class="stat-box">
        <div class="stat-number"><?php echo $totalUpcoming; ?></div>
        <div class="stat-label">Upcoming Sessions</div>
      </div>
      <div class="stat-box">
        <div class="stat-number"><?php echo $totalRsvps; ?></div>
        <div class="stat-label">Student RSVPs</div>
      </div>
      <div class="stat-box">
        <div class="stat-number"><?php echo count($myJoined); ?></div>
        <div class="stat-label">Joined by You</div>
      </div>
    </div>

    <?php if ($currentUser !== '' || $isAdmin) { ?>
      <details class="new-post">
        <summary class="connectBtn">＋ Host a Peer Teaching Session</summary>
        <form id="peerSessionForm" class="ajax-form" action="save_peer_session.php" method="POST" style="max-width:780px; margin:14px 0 0;">
          <?php echo csrf_field(); ?>
          <div class="form-grid-2">
            <div class="form-group">
              <label for="title">Session Title</label>
              <input type="text" id="title" name="title" maxlength="180" required placeholder="e.g. Intro to Git & GitHub for Beginners">
            </div>
            <div class="form-group">
              <label for="skill_topic">Skill / Topic</label>
              <input type="text" id="skill_topic" name="skill_topic" maxlength="100" required placeholder="e.g. Git, Web Dev, Python, DSA">
            </div>
            <div class="form-group">
              <label for="session_date">Date</label>
              <input type="date" id="session_date" name="session_date" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" required>
            </div>
            <div class="form-group">
              <label for="session_time">Start Time</label>
              <input type="text" id="session_time" name="session_time" maxlength="30" required placeholder="e.g. 4:30 PM">
            </div>
            <div class="form-group">
              <label for="duration_mins">Duration</label>
              <select id="duration_mins" name="duration_mins">
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60" selected>60 minutes (1 hour)</option>
                <option value="90">90 minutes (1.5 hours)</option>
                <option value="120">120 minutes (2 hours)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="mode">Session Mode</label>
              <select id="mode" name="mode">
                <option value="Online">🌐 Online (Google Meet / Zoom)</option>
                <option value="Campus">🏫 On-Campus (Lab / Classroom)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="location_link">Meeting Link or Room Location</label>
              <input type="text" id="location_link" name="location_link" maxlength="400" required placeholder="e.g. https://meet.google.com/... or CSE Lab 3">
            </div>
            <div class="form-group">
              <label for="max_seats">Max Seats</label>
              <input type="number" id="max_seats" name="max_seats" min="2" max="200" value="20" required>
            </div>
          </div>
          <div class="form-group">
            <label for="description">What will students learn? (Agenda & Prerequisites)</label>
            <textarea id="description" name="description" maxlength="1500" required placeholder="Describe what topics you'll cover, whether laptops are needed, and who should attend..."></textarea>
          </div>
          <button type="submit">Schedule Peer Session</button>
        </form>
      </details>
    <?php } else { ?>
      <div class="notice">
        Want to join or host a peer teaching session? <a href="login.php">Log in</a> or <a href="profile.php">create a student profile</a> to RSVP!
      </div>
    <?php } ?>

    <form class="search-form" action="peer_sessions.php" method="GET" style="margin-bottom:16px;">
      <input type="hidden" name="filter" value="<?php echo h($filter); ?>">
      <input type="text" name="search" placeholder="Search sessions by skill, topic, or mentor name..." value="<?php echo h($search); ?>">
      <button type="submit">Search</button>
      <?php if ($search !== '') { ?>
        <a href="peer_sessions.php?filter=<?php echo urlencode($filter); ?>" class="btn-outline">Clear</a>
      <?php } ?>
    </form>

    <div class="chips">
      <a href="peer_sessions.php?filter=upcoming" class="chip <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">🟢 Upcoming</a>
      <?php if ($currentUser !== '') { ?>
        <a href="peer_sessions.php?filter=joined" class="chip <?php echo $filter === 'joined' ? 'active' : ''; ?>">✓ Joined by Me (<?php echo count($myJoined); ?>)</a>
        <a href="peer_sessions.php?filter=hosted" class="chip <?php echo $filter === 'hosted' ? 'active' : ''; ?>">🎤 Hosted by Me</a>
      <?php } ?>
      <a href="peer_sessions.php?filter=completed" class="chip <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
      <a href="peer_sessions.php?filter=all" class="chip <?php echo $filter === 'all' ? 'active' : ''; ?>">All Sessions</a>
    </div>

    <?php if (count($sessions) > 0) { ?>
      <div class="announce-list">
        <?php foreach ($sessions as $s) {
          $isHost = ($currentUser !== '' && $s['host_name'] === $currentUser);
          $isJoined = isset($myJoined[$s['id']]);
          $joinedCount = intval($s['joined_count']);
          $maxSeats = max(1, intval($s['max_seats']));
          $pct = min(100, round(($joinedCount / $maxSeats) * 100));
          $isFull = ($joinedCount >= $maxSeats);
          $isUpcoming = ($s['status'] === 'upcoming');

          $participants = [];
          if ($isHost || $isAdmin) {
            $pq = $conn->prepare("SELECT user_name, created_at FROM peer_session_participants WHERE session_id = ? ORDER BY created_at ASC");
            if ($pq) {
              $pq->bind_param("i", $s['id']);
              $pq->execute();
              $pr = $pq->get_result();
              if ($pr) { $participants = $pr->fetch_all(MYSQLI_ASSOC); }
              $pq->close();
            }
          }
        ?>
          <div class="announce-card session-card <?php echo $s['featured'] ? 'is-pinned' : ''; ?> <?php echo !$isUpcoming ? 'is-closed' : ''; ?>">
            <div class="announce-top">
              <span class="category-tag"><?php echo h($s['skill_topic']); ?></span>
              <?php if ($currentUser !== '' && isset($GLOBALS['sc_prev_seen_session_id']) && intval($s['id']) > intval($GLOBALS['sc_prev_seen_session_id'])) { ?><span class="pin-badge" style="background:var(--accent); color:#fff; border-color:var(--accent);">✨ NEW</span><?php } ?>
              <span class="session-mode-tag <?php echo strtolower($s['mode']) === 'online' ? 'online' : 'campus'; ?>">
                <?php echo strtolower($s['mode']) === 'online' ? '🌐 Online' : '🏫 On-Campus'; ?>
              </span>
              <?php if ($s['featured']) { ?><span class="pin-badge">⭐ Featured</span><?php } ?>
              <span class="deadline-tag <?php echo $isUpcoming ? 'soon' : 'closed'; ?>">
                <?php echo $isUpcoming ? 'Upcoming' : ucfirst(h($s['status'])); ?>
              </span>
            </div>

            <h3><?php echo h($s['title']); ?></h3>
            <p class="announce-body"><?php echo nl2br(h($s['description'])); ?></p>

            <div class="session-info-box">
              <div><strong>📅 Date &amp; Time:</strong> <?php echo h(date("D, M j, Y", strtotime($s['session_date']))); ?> · <?php echo h($s['session_time']); ?> (<?php echo intval($s['duration_mins']); ?> mins)</div>
              <div>
                <strong>🎤 Peer Mentor:</strong>
                <?php if ($s['host_id']) { ?>
                  <a href="profile_view.php?id=<?php echo intval($s['host_id']); ?>"><?php echo h($s['host_name']); ?></a>
                <?php } else { echo h($s['host_name']); } ?>
                <?php if ($s['host_branch']) { ?> <span class="announce-meta">(<?php echo h($s['host_branch']); ?><?php echo $s['host_year'] ? ', ' . h($s['host_year']) : ''; ?>)</span><?php } ?>
              </div>
              <div>
                <strong>📍 Location / Link:</strong>
                <?php if ($isJoined || $isHost || $isAdmin) { ?>
                  <?php if (preg_match('#^https?://#i', $s['location_link'])) { ?>
                    <a href="<?php echo h($s['location_link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo h($s['location_link']); ?></a>
                  <?php } else { ?>
                    <span><?php echo h($s['location_link']); ?></span>
                  <?php } ?>
                <?php } else { ?>
                  <span class="announce-meta"><?php echo strtolower($s['mode']) === 'online' ? '🔒 Meeting link unlocked upon RSVP' : h($s['location_link']); ?></span>
                <?php } ?>
              </div>
            </div>

            <div class="seat-meter-wrap">
              <div class="seat-meter-labels">
                <span>👥 <strong><?php echo $joinedCount; ?> / <?php echo $maxSeats; ?></strong> seats reserved</span>
                <span><?php echo max(0, $maxSeats - $joinedCount); ?> spots left</span>
              </div>
              <div class="seat-meter-bar">
                <div class="seat-meter-fill" style="width: <?php echo $pct; ?>%;"></div>
              </div>
            </div>

            <div class="card-actions">
              <?php if ($currentUser !== '' && !$isHost && $isUpcoming) { ?>
                <form class="ajax-form inline-form" action="join_peer_session.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="session_id" value="<?php echo intval($s['id']); ?>">
                  <?php if ($isJoined) { ?>
                    <button type="submit" class="btn-outline active">✓ Joined · Click to Leave</button>
                  <?php } elseif ($isFull) { ?>
                    <button type="button" disabled>Session Full</button>
                  <?php } else { ?>
                    <button type="submit" class="connectBtn">Reserve Seat (RSVP)</button>
                  <?php } ?>
                </form>
              <?php } elseif ($currentUser === '' && $isUpcoming) { ?>
                <a href="login.php" class="connectBtn" style="text-decoration:none;">Log in to RSVP</a>
              <?php } ?>

              <?php if ($isHost || $isAdmin) { ?>
                <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                  <input type="hidden" name="action" value="<?php echo $isUpcoming ? 'complete' : 'reopen'; ?>">
                  <button type="submit" class="btn-outline"><?php echo $isUpcoming ? '✓ Mark Completed' : '↺ Reopen'; ?></button>
                </form>
                <form class="ajax-form inline-form" action="peer_session_action.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn-outline danger">Delete</button>
                </form>
              <?php } ?>
            </div>

            <?php if (($isHost || $isAdmin) && count($participants) > 0) { ?>
              <div class="response-list">
                <p class="announce-meta"><strong>Registered Participants (<?php echo count($participants); ?>):</strong>
                  <?php
                  $pNames = array_map(function($p) { return h($p['user_name']); }, $participants);
                  echo implode(', ', $pNames);
                  ?>
                </p>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3>No peer sessions match your filter</h3>
        <p>Try switching to "All Sessions" or click "＋ Host a Peer Teaching Session" above to schedule one!</p>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
