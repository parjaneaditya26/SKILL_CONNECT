<?php
mysqli_report(MYSQLI_REPORT_OFF);

function tableHasColumn($conn, $table, $column) {
  if (!$conn || !($conn instanceof mysqli)) { return false; }
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($column);
  $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
  return ($res && $res->num_rows > 0);
}

function ensureSchema($conn) {
  if (!$conn || !($conn instanceof mysqli)) { return; }
  static $checked = false;
  if ($checked) { return; }
  $checked = true;

  // 1. Announcements table
  $conn->query("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    category VARCHAR(30) NOT NULL DEFAULT 'General',
    link VARCHAR(500) DEFAULT NULL,
    deadline DATE DEFAULT NULL,
    pinned TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  // 2. Announcement interest table
  if (tableHasColumn($conn, 'announcement_interest', 'id') && !tableHasColumn($conn, 'announcement_interest', 'user_name')) {
    $conn->query("DROP TABLE IF EXISTS announcement_interest");
  }
  $conn->query("CREATE TABLE IF NOT EXISTS announcement_interest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_interest (announcement_id, user_name)
  )");

  // 3. Saved announcements table — auto-repair if an older table exists without user_name or announcement_id
  if (
    !tableHasColumn($conn, 'saved_announcements', 'user_name') ||
    !tableHasColumn($conn, 'saved_announcements', 'announcement_id') ||
    !tableHasColumn($conn, 'saved_announcements', 'created_at')
  ) {
    $conn->query("DROP TABLE IF EXISTS saved_announcements");
  }
  $conn->query("CREATE TABLE IF NOT EXISTS saved_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_saved_announcement (announcement_id, user_name)
  )");

  // 4. Help posts & responses tables
  $conn->query("CREATE TABLE IF NOT EXISTS help_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    title VARCHAR(150) NOT NULL,
    details TEXT NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS help_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    responder_name VARCHAR(100) NOT NULL,
    message VARCHAR(300) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_response (post_id, responder_name)
  )");

  // 5. Peer sessions table — auto-repair if an older table exists with different columns
  if (
    !tableHasColumn($conn, 'peer_sessions', 'host_name') ||
    !tableHasColumn($conn, 'peer_sessions', 'skill_topic') ||
    !tableHasColumn($conn, 'peer_sessions', 'session_date') ||
    !tableHasColumn($conn, 'peer_sessions', 'featured') ||
    !tableHasColumn($conn, 'peer_sessions', 'max_seats') ||
    !tableHasColumn($conn, 'peer_sessions', 'location_link')
  ) {
    $conn->query("DROP TABLE IF EXISTS peer_sessions");
  }
  $conn->query("CREATE TABLE IF NOT EXISTS peer_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_name VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL,
    skill_topic VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    session_date DATE NOT NULL,
    session_time VARCHAR(30) NOT NULL,
    duration_mins INT NOT NULL DEFAULT 60,
    mode VARCHAR(20) NOT NULL DEFAULT 'Online',
    location_link VARCHAR(400) NOT NULL,
    max_seats INT NOT NULL DEFAULT 20,
    status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  // 6. Peer session participants table — auto-repair if an older table exists with different columns
  if (
    !tableHasColumn($conn, 'peer_session_participants', 'session_id') ||
    !tableHasColumn($conn, 'peer_session_participants', 'user_name')
  ) {
    $conn->query("DROP TABLE IF EXISTS peer_session_participants");
  }
  $conn->query("CREATE TABLE IF NOT EXISTS peer_session_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_session_participant (session_id, user_name)
  )");

  // Clean up any previously auto-seeded demo peer sessions so deleted sessions never come back
  $conn->query("DELETE FROM peer_sessions WHERE title IN (
    'Full-Stack Web Dev Crash Course (React + PHP)',
    'DSA Graph & Tree Algorithms Problem Solving',
    'Python for Data Science & Machine Learning',
    'UI/UX Prototyping in Figma for Hackathons'
  )");

  // Clean up any previously loaded sample @mit.asia demo profiles
  $conn->query("DELETE FROM users WHERE email IN (
    'aarav.deshmukh@mit.asia',
    'sneha.kulkarni@mit.asia',
    'rohan.patil@mit.asia',
    'priya.sharma@mit.asia',
    'aditya.joshi@mit.asia',
    'ananya.more@mit.asia'
  )");
}

if (isset($conn) && ($conn instanceof mysqli)) {
  ensureSchema($conn);
}

function timeAgo($datetime) {
  if (!$datetime) { return "recently"; }
  $timestamp = strtotime($datetime);
  if (!$timestamp) { return "recently"; }
  $diff = time() - $timestamp;

  if ($diff < 60) { return "just now"; }
  if ($diff < 3600) { $m = floor($diff / 60); return $m . ($m == 1 ? " minute ago" : " minutes ago"); }
  if ($diff < 86400) { $h = floor($diff / 3600); return $h . ($h == 1 ? " hour ago" : " hours ago"); }
  if ($diff < 604800) { $d = floor($diff / 86400); return $d . ($d == 1 ? " day ago" : " days ago"); }
  return date("M j, Y", $timestamp);
}

function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_field() {
  return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify($token) {
  return isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function skillTags($skillsStr) {
  if (!$skillsStr || $skillsStr == "Nothing yet") {
    return '<span class="skill-tag skill-tag-empty">Nothing yet</span>';
  }
  $skills = array_map('trim', explode(',', $skillsStr));
  $html = '';
  foreach ($skills as $s) {
    if ($s != '') {
      $html .= '<span class="skill-tag">' . htmlspecialchars($s) . '</span>';
    }
  }
  return $html;
}

function skillsMatch($mySkillsStr, $theirSkillsStr) {
  if (!$mySkillsStr || !$theirSkillsStr) { return false; }
  $mySkills = array_map('trim', explode(',', strtolower($mySkillsStr)));
  $theirSkills = array_map('trim', explode(',', strtolower($theirSkillsStr)));
  foreach ($mySkills as $m) {
    foreach ($theirSkills as $t) {
      if ($m != '' && $t != '' && (stripos($t, $m) !== false || stripos($m, $t) !== false)) {
        return true;
      }
    }
  }
  return false;
}

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function announcementCategories() {
  return ["Placements", "Internships", "Scholarships", "Exams", "Workshops", "Hackathons", "General"];
}

function deadlineInfo($daysLeft) {
  if ($daysLeft === null || $daysLeft === '') { return null; }
  $d = intval($daysLeft);
  if ($d < 0) { return ["Closed", "closed"]; }
  if ($d == 0) { return ["Closes today", "soon"]; }
  if ($d == 1) { return ["Closes tomorrow", "soon"]; }
  if ($d <= 3) { return ["Closes in $d days", "soon"]; }
  return ["Closes in $d days", ""];
}

function matchingSkills($wantedStr, $offeredStr) {
  $found = [];
  if (!$wantedStr || !$offeredStr) { return $found; }
  if (strtolower(trim($wantedStr)) == "nothing yet" || strtolower(trim($offeredStr)) == "nothing yet") { return $found; }
  $wanted = array_filter(array_map('trim', explode(',', strtolower($wantedStr))));
  $offered = array_filter(array_map('trim', explode(',', $offeredStr)));
  foreach ($offered as $o) {
    foreach ($wanted as $w) {
      if (stripos($o, $w) !== false || stripos($w, strtolower($o)) !== false) {
        $found[] = $o;
        break;
      }
    }
  }
  return $found;
}

function connectionStatus($conn, $a, $b) {
  $stmt = $conn->prepare("SELECT status FROM requests WHERE (requester_name = ? AND target_name = ?) OR (requester_name = ? AND target_name = ?) ORDER BY id DESC LIMIT 1");
  if (!$stmt) { return null; }
  $stmt->bind_param("ssss", $a, $b, $b, $a);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ? $row['status'] : null;
}

function cleanupUserData($conn, $name) {
  ensureSchema($conn);
  $s = $conn->prepare("DELETE FROM announcement_interest WHERE user_name = ?");
  if ($s) { $s->bind_param("s", $name); $s->execute(); $s->close(); }

  $s = $conn->prepare("DELETE FROM saved_announcements WHERE user_name = ?");
  if ($s) { $s->bind_param("s", $name); $s->execute(); $s->close(); }

  $s = $conn->prepare("DELETE FROM peer_session_participants WHERE user_name = ? OR session_id IN (SELECT id FROM peer_sessions WHERE host_name = ?)");
  if ($s) { $s->bind_param("ss", $name, $name); $s->execute(); $s->close(); }

  $s = $conn->prepare("DELETE FROM peer_sessions WHERE host_name = ?");
  if ($s) { $s->bind_param("s", $name); $s->execute(); $s->close(); }

  $s = $conn->prepare("DELETE FROM help_responses WHERE responder_name = ? OR post_id IN (SELECT id FROM help_posts WHERE author_name = ?)");
  if ($s) { $s->bind_param("ss", $name, $name); $s->execute(); $s->close(); }

  $s = $conn->prepare("DELETE FROM help_posts WHERE author_name = ?");
  if ($s) { $s->bind_param("s", $name); $s->execute(); $s->close(); }
}

function siteNav($currentUser = '') {
  global $conn;
  if ($currentUser === '' && isset($_SESSION['profileName'])) {
    $currentUser = $_SESSION['profileName'];
  }
  $savedCount = 0;
  $upcomingSessions = 0;

  if (isset($conn) && ($conn instanceof mysqli)) {
    ensureSchema($conn);
    $resS = $conn->query("SELECT COUNT(*) AS c FROM peer_sessions WHERE status = 'upcoming'");
    if ($resS) { $upcomingSessions = intval($resS->fetch_assoc()['c']); }
    if ($currentUser !== '') {
      $stmtSaved = $conn->prepare("SELECT COUNT(*) AS c FROM saved_announcements WHERE user_name = ?");
      if ($stmtSaved) {
        $stmtSaved->bind_param("s", $currentUser);
        $stmtSaved->execute();
        $rSaved = $stmtSaved->get_result();
        if ($rSaved) { $savedCount = intval($rSaved->fetch_assoc()['c']); }
        $stmtSaved->close();
      }
    }
  }
  ?>
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">🏠 Home</a>
      <?php if ($currentUser) { ?>
        <a href="profile.php" class="nav-link">👤 My Profile</a>
        <a href="browse.php" class="nav-link">🔍 Browse Skills</a>
        <a href="peer_sessions.php" class="nav-link">
          🎓 Peer Sessions
          <?php if ($upcomingSessions > 0) { ?><span class="notif-badge subtle"><?php echo $upcomingSessions; ?></span><?php } ?>
        </a>
        <a href="announcements.php" class="nav-link">📢 Announcements</a>
        <a href="saved_announcements.php" class="nav-link">
          🔖 Saved Notices
          <?php if ($savedCount > 0) { ?><span class="notif-badge subtle"><?php echo $savedCount; ?></span><?php } ?>
        </a>
        <a href="help_board.php" class="nav-link">🤝 Help Board</a>
        <a href="requests.php" class="nav-link">📬 My Requests</a>
        <a href="connections.php" class="nav-link">💬 My Connections</a>
        <a href="logout.php" class="nav-link" style="margin-top:10px; border-top:1px solid var(--border); padding-top:12px; color:var(--danger); font-weight:600;">🚪 Logout (<?php echo h($currentUser); ?>)</a>
      <?php } elseif (!empty($_SESSION['isAdmin'])) { ?>
        <a href="admin.php" class="nav-link" style="font-weight:700; color:var(--accent-dark);">🛡️ Back to Admin Dashboard</a>
        <a href="peer_sessions.php" class="nav-link">🎓 Peer Sessions</a>
        <a href="admin_announcements.php" class="nav-link">📢 Announcements</a>
        <a href="admin_logout.php" class="nav-link" style="margin-top:10px; border-top:1px solid var(--border); padding-top:12px; color:var(--danger); font-weight:600;">🚪 Admin Logout</a>
      <?php } else { ?>
        <a href="profile.php" class="nav-link">✨ Create Profile</a>
        <a href="login.php" class="nav-link">🔑 Student Login</a>
      <?php } ?>
    </div>
  </nav>
  <?php
}

function adminNav() {
  ?>
  <nav class="admin-sidebar">
    <a href="index.php?view_site=1" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="admin-role-pill">🛡️ Admin Control Center</div>
    <div class="nav-links">
      <a href="admin.php" class="nav-link">📊 Dashboard Overview</a>
      <a href="admin.php#users-section" class="nav-link">👥 Manage Students</a>
      <a href="admin.php#sessions-section" class="nav-link">🎓 Peer Sessions</a>
      <a href="admin_announcements.php" class="nav-link">📢 Announcements</a>
      <a href="admin.php#help-section" class="nav-link">🤝 Help Board</a>
      <a href="admin.php#requests-section" class="nav-link">🔄 Skill Connections</a>
      <a href="admin.php#reports-section" class="nav-link">🚩 User Reports</a>
      <div class="nav-divider"></div>
      <a href="index.php?view_site=1" class="nav-link">🌐 View Student Site</a>
      <a href="peer_sessions.php" class="nav-link">🎓 Live Peer Sessions</a>
      <a href="admin_logout.php" class="nav-link">🚪 Admin Logout</a>
    </div>
  </nav>
  <?php
}

function renderChatbot() {
  ?>
  <div id="chatbot-window" aria-live="polite">
    <div id="chatbot-header">
      <div class="chatbot-header-info">
        <span class="chatbot-avatar-dot">🤖</span>
        <div>
          <div class="chatbot-title">Skill Connect AI</div>
          <div class="chatbot-subtitle"><span class="online-dot"></span> Smart Campus Assistant</div>
        </div>
      </div>
      <div class="chatbot-header-actions">
        <button id="chatbot-clear" type="button" title="Clear conversation">↺ Reset</button>
        <button id="chatbot-close" type="button" title="Minimize chat">✕</button>
      </div>
    </div>
    <div id="chatbot-suggestions">
      <button type="button" class="chat-chip" data-prompt="Find a mentor who can teach Python or Web Development">🔍 Find a Mentor</button>
      <button type="button" class="chat-chip" data-prompt="Show upcoming peer teaching sessions">🎓 Peer Sessions</button>
      <button type="button" class="chat-chip" data-prompt="What are the latest college announcements and deadlines?">📢 Announcements</button>
      <button type="button" class="chat-chip" data-prompt="Who is my best skill swap match?">🤝 My Matches</button>
      <button type="button" class="chat-chip" data-prompt="Give me a 4-step learning roadmap for Data Structures & Algorithms">🗺️ DSA Roadmap</button>
    </div>
    <div id="chatbot-messages">
      <div class="chat-msg bot">Hi! I'm your <strong>Skill Connect Smart AI</strong>. I remember our chat as you move across pages! Ask me to find mentors, check upcoming peer sessions, summarize announcements, or build a learning roadmap.</div>
    </div>
    <div id="chatbot-input-row">
      <input type="text" id="chatbot-input" placeholder="Ask about mentors, sessions, skills, notices...">
      <button id="chatbot-send" type="button">Send ➤</button>
    </div>
  </div>
  <button id="chatbot-toggle" type="button" aria-label="Open Skill Connect AI Assistant">
    <span class="chatbot-toggle-icon">💬</span>
    <span class="chatbot-toggle-label">Ask AI</span>
  </button>
  <?php
}

function layoutStart($title, $heading, $subtitle = '', $mode = 'user') {
  $currentUser = (isset($_SESSION['profileName'])) ? $_SESSION['profileName'] : '';
  $scriptName = basename($_SERVER['PHP_SELF'] ?? '');
  if ($mode === 'user' && $currentUser === '' && !empty($_SESSION['isAdmin']) && !in_array($scriptName, ['login.php', 'profile.php'], true)) {
    $mode = 'admin';
  }
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($title); ?> - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo h($currentUser); ?>">
  <?php if ($mode === 'admin') { adminNav(); } else { siteNav($currentUser); } ?>
  <header>
    <h1><?php echo h($heading); ?></h1>
    <?php if ($subtitle !== '') { ?><p><?php echo h($subtitle); ?></p><?php } ?>
  </header>
  <main>
<?php
}

function layoutEnd($mode = 'user') {
  ?>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP &amp; MySQL</p>
  </footer>
  <?php renderChatbot(); ?>
  <script src="script.js?v=3"></script>
</body>
</html>
<?php
}

function renderLoginRequired($title, $subtitle) {
  layoutStart($title, $title, $subtitle);
  ?>
  <div class="empty-state">
    <h3>Please log in</h3>
    <p>Please log in with your student account or create a profile to access this page.</p>
    <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:14px;">
      <a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none;">Student Log In</a>
      <a href="profile.php" class="btn-outline" style="display:inline-block; text-decoration:none;">Create Profile</a>
    </div>
  </div>
  <?php
  layoutEnd();
  exit;
}

function textLen($s) {
  return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen((string)$s);
}

function textCut($s, $len) {
  if (function_exists('mb_substr')) { return mb_substr($s, 0, $len, 'UTF-8'); }
  preg_match('/^.{0,' . intval($len) . '}/us', $s, $m);
  return isset($m[0]) ? $m[0] : '';
}
?>
