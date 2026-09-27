<?php
session_start();
include 'db_connect.php';

$loggedIn = isset($_SESSION['profileName']);

$memberCount = 0;
$connectionCount = 0;

$result1 = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result1) {
  $row1 = $result1->fetch_assoc();
  $memberCount = $row1['total'];
}

$result2 = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status = 'accepted'");
if ($result2) {
  $row2 = $result2->fetch_assoc();
  $connectionCount = $row2['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo $loggedIn ? htmlspecialchars($_SESSION['profileName']) : ''; ?>">

  <nav>
    <a href="index.php">Home</a>
    <?php if ($loggedIn) { ?>
      <a href="profile.php">My Profile</a>
      <a href="browse.php">Browse Skills</a>
      <a href="requests.php">My Requests</a>
      <a href="connections.php">My Connections</a>
      <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['profileName']); ?>)</a>
    <?php } else { ?>
      <a href="profile.php">Create Profile</a>
      <a href="login.php">Login</a>
    <?php } ?>
  </nav>

  <header>
    <h1>Skill Connect</h1>
    <p>Learn a skill. Teach a skill. Connect with people.</p>
    <br>
    <button id="getStartedBtn">Get Started</button>
  </header>

  <div class="stats-section">
    <div class="stat-box">
      <div class="stat-number"><?php echo $memberCount; ?></div>
      <div class="stat-label">Members</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?php echo $connectionCount; ?></div>
      <div class="stat-label">Connections Made</div>
    </div>
  </div>

  <div class="how-it-works">
    <h2>How It Works</h2>
    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Create Your Profile</h3>
        <p>Tell us what skill you can teach and what you want to learn.</p>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <h3>Browse & Search</h3>
        <p>Find people whose skills match what you're looking for.</p>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <h3>Connect</h3>
        <p>Send a request and start learning from each other.</p>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP & MySQL</p>
  </footer>
</body>
</html>