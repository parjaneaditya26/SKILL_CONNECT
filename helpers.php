<?php
function timeAgo($datetime) {
  $timestamp = strtotime($datetime);
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
  $stmt->bind_param("ssss", $a, $b, $b, $a);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  return $row ? $row['status'] : null;
}

function cleanupUserData($conn, $name) {
  $s = $conn->prepare("DELETE FROM announcement_interest WHERE user_name = ?");
  $s->bind_param("s", $name); $s->execute(); $s->close();

  $s = $conn->prepare("DELETE FROM help_responses WHERE responder_name = ? OR post_id IN (SELECT id FROM help_posts WHERE author_name = ?)");
  $s->bind_param("ss", $name, $name); $s->execute(); $s->close();

  $s = $conn->prepare("DELETE FROM help_posts WHERE author_name = ?");
  $s->bind_param("s", $name); $s->execute(); $s->close();
}

function siteNav($currentUser) {
  ?>
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <?php if ($currentUser) { ?>
        <a href="profile.php" class="nav-link">My Profile</a>
        <a href="browse.php" class="nav-link">Browse Skills</a>
        <a href="announcements.php" class="nav-link">Announcements</a>
        <a href="help_board.php" class="nav-link">Help Board</a>
        <a href="requests.php" class="nav-link">My Requests</a>
        <a href="connections.php" class="nav-link">My Connections</a>
        <a href="logout.php" class="nav-link">Logout (<?php echo h($currentUser); ?>)</a>
      <?php } else { ?>
        <a href="profile.php" class="nav-link">Create Profile</a>
        <a href="login.php" class="nav-link">Login</a>
      <?php } ?>
    </div>
  </nav>
  <?php
}

function adminNav() {
  ?>
  <nav>
    <a href="index.php" class="logo-link">
      <span class="logo-badge">SC</span>
      <span class="logo-text">Skill<strong>Connect</strong></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <a href="admin.php" class="nav-link">Admin Dashboard</a>
      <a href="admin_announcements.php" class="nav-link">Announcements</a>
      <a href="admin_logout.php" class="nav-link">Admin Logout</a>
    </div>
  </nav>
  <?php
}

function layoutStart($title, $heading, $subtitle = '', $mode = 'user') {
  $currentUser = ($mode === 'user' && isset($_SESSION['profileName'])) ? $_SESSION['profileName'] : '';
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
    <p>Built with HTML, CSS, JavaScript, PHP & MySQL</p>
  </footer>
  <?php if ($mode !== 'admin') { ?>
  <div id="chatbot-window">
    <div id="chatbot-header">💬 Skill Connect Assistant</div>
    <div id="chatbot-messages">
      <div class="chat-msg bot">Hi! I'm the Skill Connect assistant. Ask me how to use the site, or for skill-learning advice.</div>
    </div>
    <div id="chatbot-input-row">
      <input type="text" id="chatbot-input" placeholder="Ask a question...">
      <button id="chatbot-send" type="button">Send</button>
    </div>
  </div>
  <button id="chatbot-toggle">💬</button>
  <?php } ?>
  <script src="script.js"></script>
</body>
</html>
<?php
}

function renderLoginRequired($title, $subtitle) {
  layoutStart($title, $title, $subtitle);
  ?>
  <div class="empty-state">
    <h3>Please log in</h3>
    <p>You need to be logged in to see this page.</p>
    <a href="login.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Log In</a>
  </div>
  <?php
  layoutEnd();
  exit;
}

function textLen($s) {
  return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen(utf8_decode($s));
}

function textCut($s, $len) {
  if (function_exists('mb_substr')) { return mb_substr($s, 0, $len, 'UTF-8'); }
  preg_match('/^.{0,' . intval($len) . '}/us', $s, $m);
  return isset($m[0]) ? $m[0] : '';
}
?>
