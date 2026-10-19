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

  // 8. Per-user seen state for Peer Sessions & Announcements notification badges
  $conn->query("CREATE TABLE IF NOT EXISTS user_seen_state (
    user_name VARCHAR(100) PRIMARY KEY,
    seen_session_id INT NOT NULL DEFAULT 0,
    seen_announcement_id INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  )");

  // 9. Platform settings table (tracks one-time initial seed so deleted items NEVER come back)
  $conn->query("CREATE TABLE IF NOT EXISTS platform_settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  )");

  // One-time starter data: 3 Students, 3 Peer Sessions, 2 College Announcements, 2 Help Desk Posts.
  // Tracked by 'starter_pack_3_3_2_2_v1' in platform_settings so if Admin deletes any item later, it stays deleted permanently!
  $resStarter = $conn->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'starter_pack_3_3_2_2_v1' LIMIT 1");
  if (!$resStarter || $resStarter->num_rows === 0) {
    $conn->query("INSERT INTO platform_settings (setting_key, setting_value) VALUES ('starter_pack_3_3_2_2_v1', '1')");

    $defaultHash = password_hash('student123', PASSWORD_DEFAULT);

    // 1. Seed 3 Students (only if they don't already exist)
    $starterUsers = [
      ['Aarav Deshmukh', 'aarav.deshmukh@gmail.com', '9823011223', 'Computer Engineering', 'TY', 'React, JavaScript, PHP, Git', 'Python, Machine Learning'],
      ['Sneha Kulkarni', 'sneha.kulkarni@gmail.com', '9823044556', 'Information Technology', 'Final Year', 'Data Structures, C++, Java, SQL', 'React, UI/UX Design'],
      ['Rohan Patil', 'rohan.patil@gmail.com', '9823077889', 'Computer Engineering', 'TY', 'Python, Machine Learning, Data Science', 'React, PHP']
    ];
    $stmtU = $conn->prepare("INSERT INTO users (name, email, mobile, password, branch, year_of_study, teach_skill, learn_skill, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    if ($stmtU) {
      foreach ($starterUsers as $su) {
        $chkU = $conn->query("SELECT id FROM users WHERE email = '" . $conn->real_escape_string($su[1]) . "' OR name = '" . $conn->real_escape_string($su[0]) . "' LIMIT 1");
        if (!$chkU || $chkU->num_rows === 0) {
          $stmtU->bind_param("ssssssss", $su[0], $su[1], $su[2], $defaultHash, $su[3], $su[4], $su[5], $su[6]);
          $stmtU->execute();
        }
      }
      $stmtU->close();
    }

    // 2. Seed 3 Peer Teaching Sessions
    $d1 = date('Y-m-d', strtotime('+3 days'));
    $d2 = date('Y-m-d', strtotime('+5 days'));
    $d3 = date('Y-m-d', strtotime('+7 days'));
    $starterSessions = [
      ['Aarav Deshmukh', 'Full-Stack Web Dev Crash Course (React + PHP)', 'Web Development', 'Hands-on peer workshop covering frontend components, REST APIs, and connecting PHP & MySQL backends.', $d1, '4:30 PM', 90, 'Online', 'https://meet.google.com/sc-webdev-mit', 25, 1],
      ['Sneha Kulkarni', 'DSA Graph & Tree Algorithms Problem Solving', 'Data Structures & Algorithms', 'Live coding and placement-focused walkthrough of BFS, DFS, Dijkstra, and Tree DP questions.', $d2, '5:00 PM', 60, 'Campus', 'CSE Lab 2, Main Building (2nd Floor)', 20, 1],
      ['Rohan Patil', 'Python for Data Science & Machine Learning', 'Python & ML', 'Practical session on NumPy, Pandas, data cleaning, and training your first Scikit-learn model.', $d3, '3:30 PM', 75, 'Online', 'https://meet.google.com/sc-python-ml', 30, 0]
    ];
    $stmtS = $conn->prepare("INSERT INTO peer_sessions (host_name, title, skill_topic, description, session_date, session_time, duration_mins, mode, location_link, max_seats, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?)");
    if ($stmtS) {
      foreach ($starterSessions as $ss) {
        $stmtS->bind_param("ssssssissii", $ss[0], $ss[1], $ss[2], $ss[3], $ss[4], $ss[5], $ss[6], $ss[7], $ss[8], $ss[9], $ss[10]);
        $stmtS->execute();
      }
      $stmtS->close();
    }

    // 3. Seed 2 College Announcements
    $dl1 = date('Y-m-d', strtotime('+6 days'));
    $dl2 = date('Y-m-d', strtotime('+10 days'));
    $starterAnnouncements = [
      ['TCS Digital & Ninja Campus Recruitment Drive 2026', 'Final Year (BE/B.Tech all branches) students with 60%+ aggregate can register for the upcoming TCS Campus Drive. Aptitude and coding test in Main Auditorium.', 'Placements', 'https://www.tcs.com/careers', $dl1, 1],
      ['Smart India Hackathon (SIH) Internal Shortlisting', 'Form teams of 6 students (at least 1 female member mandatory) and submit your problem statement presentation to the MIT CSN Innovation Cell.', 'Hackathons', 'https://www.sih.gov.in', $dl2, 1]
    ];
    $stmtA = $conn->prepare("INSERT INTO announcements (title, body, category, link, deadline, pinned) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmtA) {
      foreach ($starterAnnouncements as $sa) {
        $stmtA->bind_param("sssssi", $sa[0], $sa[1], $sa[2], $sa[3], $sa[4], $sa[5]);
        $stmtA->execute();
      }
      $stmtA->close();
    }

    // 4. Seed 2 Help Desk Posts (help_posts)
    $starterHelp = [
      ['Aarav Deshmukh', 'teammates', 'Need 2 teammates for Smart India Hackathon (Frontend + ML)', 'We are building an AI crop disease detector for SIH 2026. Looking for a Python/ML developer and a UI designer from MIT CSN.'],
      ['Sneha Kulkarni', 'help', 'Need help debugging PHP & MySQL query in XAMPP', 'Working on a college database mini-project and need 15 minutes of help optimizing a JOIN query and fixing form validation.']
    ];
    $stmtH = $conn->prepare("INSERT INTO help_posts (author_name, type, title, details, status) VALUES (?, ?, ?, ?, 'open')");
    if ($stmtH) {
      foreach ($starterHelp as $sh) {
        $stmtH->bind_param("ssss", $sh[0], $sh[1], $sh[2], $sh[3]);
        $stmtH->execute();
      }
      $stmtH->close();
    }
  }
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
  $unreadSessions = 0;
  $unreadAnnouncements = 0;

  if (isset($conn) && ($conn instanceof mysqli)) {
    ensureSchema($conn);

    $maxSessionId = 0;
    $rMaxS = $conn->query("SELECT COALESCE(MAX(id), 0) AS m FROM peer_sessions");
    if ($rMaxS) { $maxSessionId = intval($rMaxS->fetch_assoc()['m']); }

    $maxAnnId = 0;
    $rMaxA = $conn->query("SELECT COALESCE(MAX(id), 0) AS m FROM announcements");
    if ($rMaxA) { $maxAnnId = intval($rMaxA->fetch_assoc()['m']); }

    if ($currentUser !== '') {
      $stmtInit = $conn->prepare("INSERT IGNORE INTO user_seen_state (user_name, seen_session_id, seen_announcement_id) VALUES (?, 0, 0)");
      if ($stmtInit) {
        $stmtInit->bind_param("s", $currentUser);
        $stmtInit->execute();
        $stmtInit->close();
      }

      $seenSessionId = 0;
      $seenAnnId = 0;
      $stmtSeen = $conn->prepare("SELECT seen_session_id, seen_announcement_id FROM user_seen_state WHERE user_name = ?");
      if ($stmtSeen) {
        $stmtSeen->bind_param("s", $currentUser);
        $stmtSeen->execute();
        $rSeen = $stmtSeen->get_result();
        if ($rSeen && ($rowSeen = $rSeen->fetch_assoc())) {
          $seenSessionId = intval($rowSeen['seen_session_id']);
          $seenAnnId = intval($rowSeen['seen_announcement_id']);
        }
        $stmtSeen->close();
      }

      // If rows were deleted/reset in DB, clamp seen IDs so new posts always notify
      if ($seenSessionId > $maxSessionId) {
        $seenSessionId = $maxSessionId;
        $stmtClS = $conn->prepare("UPDATE user_seen_state SET seen_session_id = ? WHERE user_name = ?");
        if ($stmtClS) { $stmtClS->bind_param("is", $maxSessionId, $currentUser); $stmtClS->execute(); $stmtClS->close(); }
      }
      if ($seenAnnId > $maxAnnId) {
        $seenAnnId = $maxAnnId;
        $stmtClA = $conn->prepare("UPDATE user_seen_state SET seen_announcement_id = ? WHERE user_name = ?");
        if ($stmtClA) { $stmtClA->bind_param("is", $maxAnnId, $currentUser); $stmtClA->execute(); $stmtClA->close(); }
      }

      // Save previous seen IDs for optional 'NEW' card badge on the current page view
      $GLOBALS['sc_prev_seen_session_id'] = $seenSessionId;
      $GLOBALS['sc_prev_seen_ann_id'] = $seenAnnId;

      // When user clicks and opens peer_sessions.php or announcements.php, mark all current items as read!
      $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
      if ($currentScript === 'peer_sessions.php') {
        $seenSessionId = $maxSessionId;
        $stmtUpS = $conn->prepare("UPDATE user_seen_state SET seen_session_id = ? WHERE user_name = ?");
        if ($stmtUpS) {
          $stmtUpS->bind_param("is", $maxSessionId, $currentUser);
          $stmtUpS->execute();
          $stmtUpS->close();
        }
      } elseif ($currentScript === 'announcements.php') {
        $seenAnnId = $maxAnnId;
        $stmtUpA = $conn->prepare("UPDATE user_seen_state SET seen_announcement_id = ? WHERE user_name = ?");
        if ($stmtUpA) {
          $stmtUpA->bind_param("is", $maxAnnId, $currentUser);
          $stmtUpA->execute();
          $stmtUpA->close();
        }
      }

      $resS = $conn->query("SELECT COUNT(*) AS c FROM peer_sessions WHERE status = 'upcoming' AND id > " . intval($seenSessionId));
      if ($resS) { $unreadSessions = intval($resS->fetch_assoc()['c']); }

      $resA = $conn->query("SELECT COUNT(*) AS c FROM announcements WHERE (deadline IS NULL OR deadline >= CURDATE()) AND id > " . intval($seenAnnId));
      if ($resA) { $unreadAnnouncements = intval($resA->fetch_assoc()['c']); }

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
          <?php if ($unreadSessions > 0) { ?><span class="notif-badge subtle"><?php echo $unreadSessions; ?></span><?php } ?>
        </a>
        <a href="announcements.php" class="nav-link">
          📢 Announcements
          <?php if ($unreadAnnouncements > 0) { ?><span class="notif-badge subtle"><?php echo $unreadAnnouncements; ?></span><?php } ?>
        </a>
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
  <meta name="csrf-token" content="<?php echo h(csrf_token()); ?>">
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
  <?php if ($mode !== 'admin') { renderChatbot(); } ?>
  <script src="script.js?v=4"></script>
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
