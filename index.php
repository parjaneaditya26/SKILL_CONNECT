<?php
require_once __DIR__ . '/session_init.php';
include "db_connect.php";
include "helpers.php";

ensureSchema($conn);

if (!empty($_SESSION["isAdmin"]) && !isset($_GET["view_site"])) {
  header("Location: admin.php");
  exit;
}

if (isset($_SESSION["profileName"]) && !isset($_GET["view_site"])) {
  include "dashboard.php";
  exit;
}

$loggedIn = isset($_SESSION["profileName"]);
$currentUser = $loggedIn ? $_SESSION["profileName"] : "";

$memberCount = 0;
$connectionCount = 0;

$result1 = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result1) { $row1 = $result1->fetch_assoc(); $memberCount = intval($row1["total"]); }

$result2 = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status = 'accepted'");
if ($result2) { $row2 = $result2->fetch_assoc(); $connectionCount = intval($row2["total"]); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo h(csrf_token()); ?>">
  <title>Skill Connect — MIT CSN Peer Learning Platform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo h($currentUser); ?>">

  <?php siteNav($currentUser); ?>

  <header>
    <span class="category-tag" style="margin-bottom:10px;">MIT CSN Campus Platform</span>
    <h1>Skill Connect</h1>
    <p>Learn a skill. Teach a skill. Connect with MIT CSN classmates to swap skills and grow together.</p>
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
      <button id="getStartedBtn" type="button">🚀 Get Started</button>
      <a href="login.php" class="btn-outline" style="padding:10px 20px;">🔑 Student Login</a>
    </div>
  </header>

  <div class="stats-section">
    <div class="stat-box">
      <div class="stat-number"><?php echo $memberCount; ?></div>
      <div class="stat-label">Campus Members</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?php echo $connectionCount; ?></div>
      <div class="stat-label">Connections Made</div>
    </div>
  </div>

  <div class="how-it-works">
    <h2>How Skill Connect Works</h2>
    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Create Your Skill Profile</h3>
        <p>List the skills you can teach and the skills you want to learn across engineering, coding, design, and more.</p>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <h3>Browse Skills &amp; Mentors</h3>
        <p>Search classmates by skill or branch to find the right learning partner on campus.</p>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <h3>Find 2-Way Skill Matches</h3>
        <p>Use our smart matcher or ask the AI Assistant to discover classmates whose skills complement yours.</p>
      </div>
      <div class="step">
        <div class="step-number">4</div>
        <h3>Connect, Chat &amp; Rate</h3>
        <p>Send a connection request, collaborate with your skill partner, and rate your learning experience.</p>
      </div>
    </div>
  </div>

  <footer>
    <p>Skill Connect &copy; 2026 — MIT CSN, A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP &amp; MySQL</p>
  </footer>
  <?php renderChatbot(); ?>
  <script src="script.js?v=6"></script>
</body>
</html>