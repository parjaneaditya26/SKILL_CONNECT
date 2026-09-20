<?php
session_start();
include 'db_connect.php';

$search = isset($_GET['search']) ? $_GET['search'] : "";
$category = isset($_GET['category']) ? $_GET['category'] : "";

$sql = "SELECT * FROM users WHERE 1=1";
if ($search != "") {
  $s = $conn->real_escape_string($search);
  $sql .= " AND (teach_skill LIKE '%$s%' OR learn_skill LIKE '%$s%' OR name LIKE '%$s%')";
}
if ($category != "") {
  $c = $conn->real_escape_string($category);
  $sql .= " AND category = '$c'";
}

$result = $conn->query($sql);

if (!isset($_SESSION['profileName'])) {
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Browse Skills - Skill Connect</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body data-user="">
    <nav>
      <a href="index.php">Home</a>
      <a href="profile.php">Create Profile</a>
      <a href="login.php">Login</a>
    </nav>
    <header>
      <h1>Create a Profile First</h1>
      <p>You need a profile before you can browse and connect with others.</p>
    </header>
    <main>
      <div class="empty-state">
        <h3>No profile found</h3>
        <p>Create your profile, or log in if you already have one.</p>
        <a href="profile.php" class="connectBtn" style="display:inline-block; text-decoration:none; margin-top:12px;">Create Profile</a>
      </div>
    </main>
    <footer>
      <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    </footer>
  </body>
  </html>
  <?php
  exit;
}

$myTeach = isset($_GET['my_teach']) ? $_GET['my_teach'] : "";
$myLearn = isset($_GET['my_learn']) ? $_GET['my_learn'] : "";
$currentUser = $_SESSION['profileName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Browse Skills - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo htmlspecialchars($currentUser); ?>">

  <nav>
    <a href="index.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="browse.php">Browse Skills</a>
    <a href="requests.php">My Requests</a>
    <a href="logout.php">Logout (<?php echo htmlspecialchars($currentUser); ?>)</a>
  </nav>

  <header>
    <h1>Browse Skills</h1>
    <p>Find people to learn from and teach.</p>
  </header>

  <main>
    <form class="search-form" action="browse.php" method="GET">
      <input type="text" name="search" placeholder="Search a skill or name..." value="<?php echo htmlspecialchars($search); ?>">
      <select name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <option value="Music" <?php if ($category == "Music") echo "selected"; ?>>Music</option>
        <option value="Tech" <?php if ($category == "Tech") echo "selected"; ?>>Tech</option>
        <option value="Cooking" <?php if ($category == "Cooking") echo "selected"; ?>>Cooking</option>
        <option value="Sports" <?php if ($category == "Sports") echo "selected"; ?>>Sports</option>
        <option value="Language" <?php if ($category == "Language") echo "selected"; ?>>Language</option>
        <option value="Art" <?php if ($category == "Art") echo "selected"; ?>>Art</option>
        <option value="Other" <?php if ($category == "Other") echo "selected"; ?>>Other</option>
      </select>
      <button type="submit">Search</button>
    </form>

    <form class="match-form" action="browse.php" method="GET">
      <p>Find your perfect match:</p>
      <input type="text" name="my_teach" placeholder="Skill you teach" value="<?php echo htmlspecialchars($myTeach); ?>">
      <input type="text" name="my_learn" placeholder="Skill you want to learn" value="<?php echo htmlspecialchars($myLearn); ?>">
      <button type="submit">Find Matches</button>
    </form>

    <div class="skill-list">
      <?php if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <?php
          $isMatch = false;
          if ($myTeach != "" && $myLearn != "") {
            if (stripos($row['learn_skill'], $myTeach) !== false && stripos($row['teach_skill'], $myLearn) !== false) {
              $isMatch = true;
            }
          }

          $avgResult = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM ratings WHERE target_id = " . intval($row['id']));
          $avgRow = $avgResult->fetch_assoc();
          $avgRating = $avgRow['avg_rating'] ? round($avgRow['avg_rating'], 1) : null;
          $ratingCount = $avgRow['total'];

          $showContact = false;
          if ($row['name'] == $currentUser) {
            $showContact = true;
          } else {
            $cu = $conn->real_escape_string($currentUser);
            $rn = $conn->real_escape_string($row['name']);
            $acceptedCheck = $conn->query("SELECT id FROM requests WHERE status = 'accepted' AND ((requester_name = '$cu' AND target_id = {$row['id']}) OR (requester_name = '$rn' AND target_name = '$cu'))");
            if ($acceptedCheck->num_rows > 0) {
              $showContact = true;
            }
          }
          ?>
          <div class="skill-card <?php echo $isMatch ? 'perfect-match' : ''; ?>">
            <?php if ($isMatch) { ?><span class="match-badge">✨ Perfect Match</span><?php } ?>
            <div class="avatar"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
            <span class="category-tag"><?php echo htmlspecialchars($row['category']); ?></span>
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <?php if ($avgRating) { ?>
              <p class="rating-display">⭐ <?php echo $avgRating; ?> (<?php echo $ratingCount; ?> reviews)</p>
            <?php } else { ?>
              <p class="rating-display no-rating">No ratings yet</p>
            <?php } ?>
            <p><strong>Can teach:</strong> <?php echo htmlspecialchars($row['teach_skill']); ?></p>
            <p><strong>Wants to learn:</strong> <?php echo htmlspecialchars($row['learn_skill']); ?></p>
            <?php if ($showContact) { ?>
              <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['mobile']); ?></p>
            <?php } else { ?>
              <p class="locked-contact">🔒 Contact visible after connection is accepted</p>
            <?php } ?>

            <?php if ($row['name'] == $currentUser) { ?>
              <p style="text-align:center; font-weight:600; color:#1d4ed8; margin-top:10px;">This is your profile</p>
            <?php } else { ?>
              <form action="connect.php" method="POST" class="connect-form">
                <input type="hidden" name="target_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                <button type="submit" class="connectBtn">Connect</button>
              </form>
            <?php } ?>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="empty-state">
          <h3>No profiles found</h3>
          <p><?php echo ($search != "" || $category != "") ? "Try a different search or category." : "Be the first to create a profile!"; ?></p>
        </div>
      <?php } ?>
    </div>
  </main>

  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
    <p>Built with HTML, CSS, JavaScript, PHP & MySQL</p>
  </footer>

  <script src="script.js"></script>
</body>
</html>