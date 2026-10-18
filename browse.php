<?php
session_start();
include 'db_connect.php';
include 'helpers.php';

ensureSchema($conn);

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Browse Skills", "Create a profile or log in before browsing and connecting with classmates.");
}

$search = isset($_GET['search']) ? $_GET['search'] : "";
$branch = isset($_GET['branch']) ? $_GET['branch'] : "";

$sql = "SELECT * FROM users WHERE 1=1";
if ($search != "") {
  $s = $conn->real_escape_string($search);
  $sql .= " AND (teach_skill LIKE '%$s%' OR learn_skill LIKE '%$s%' OR name LIKE '%$s%')";
}
if ($branch != "") {
  $b = $conn->real_escape_string($branch);
  $sql .= " AND branch = '$b'";
}

$result = $conn->query($sql);

$myTeach = isset($_GET['my_teach']) ? $_GET['my_teach'] : "";
$myLearn = isset($_GET['my_learn']) ? $_GET['my_learn'] : "";
$currentUser = $_SESSION['profileName'];
$csrf = csrf_token();
$branches = ["Computer Engineering","Information Technology","Mechanical Engineering","Civil Engineering","Electronics & Telecommunication","Electrical Engineering","Other"];

layoutStart("Browse Skills", "Browse Skills & Mentors", "Find classmates to learn from, teach, and swap skills with.");
?>
    <form class="search-form" action="browse.php" method="GET">
      <input type="text" name="search" placeholder="Search a skill or name..." value="<?php echo htmlspecialchars($search); ?>">
      <select name="branch" onchange="this.form.submit()">
        <option value="">All Branches</option>
        <?php foreach ($branches as $b) { ?>
          <option value="<?php echo $b; ?>" <?php if ($branch == $b) echo "selected"; ?>><?php echo $b; ?></option>
        <?php } ?>
      </select>
      <button type="submit">Search</button>
      <?php if ($search != "" || $branch != "" || $myTeach != "" || $myLearn != "") { ?>
        <a href="browse.php" class="btn-outline">Reset</a>
      <?php } ?>
    </form>

    <form class="match-form" action="browse.php" method="GET">
      <p>✨ Find your two-way skill swap match:</p>
      <input type="text" name="my_teach" placeholder="A skill you teach (e.g. Python)" value="<?php echo htmlspecialchars($myTeach); ?>">
      <input type="text" name="my_learn" placeholder="A skill you want to learn (e.g. React)" value="<?php echo htmlspecialchars($myLearn); ?>">
      <button type="submit">Find Matches</button>
    </form>

    <div class="skill-list">
      <?php if ($result && $result->num_rows > 0) { ?>
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
          $myRequestStatus = null;
          if ($row['name'] == $currentUser) {
            $showContact = true;
          } else {
            $cu = $conn->real_escape_string($currentUser);
            $rn = $conn->real_escape_string($row['name']);
            $acceptedCheck = $conn->query("SELECT id FROM requests WHERE status = 'accepted' AND ((requester_name = '$cu' AND target_id = {$row['id']}) OR (requester_name = '$rn' AND target_name = '$cu'))");
            if ($acceptedCheck && $acceptedCheck->num_rows > 0) { $showContact = true; }

            $statusCheck = $conn->query("SELECT status FROM requests WHERE (requester_name = '$cu' AND target_id = {$row['id']}) OR (requester_name = '$rn' AND target_name = '$cu') ORDER BY id DESC LIMIT 1");
            if ($statusCheck && $statusCheck->num_rows > 0) {
              $srow = $statusCheck->fetch_assoc();
              $myRequestStatus = $srow['status'];
            }
          }
          ?>
          <div class="skill-card <?php echo $isMatch ? 'perfect-match' : ''; ?>">
            <?php if ($isMatch) { ?><span class="match-badge">✨ Perfect Match</span><?php } ?>
            <div class="avatar"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
            <?php if ($row['branch']) { ?><span class="category-tag"><?php echo htmlspecialchars($row['branch']); ?></span><?php } ?>
            <?php if ($row['year_of_study']) { ?><span class="category-tag"><?php echo htmlspecialchars($row['year_of_study']); ?></span><?php } ?>
            <h3>
              <a href="profile_view.php?id=<?php echo $row['id']; ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($row['name']); ?></a>
              <?php if ($row['verified']) { ?><span style="color:var(--accent); font-size:0.78rem; font-weight:600;"> ✓ Verified</span><?php } ?>
            </h3>
            <?php if ($avgRating) { ?>
              <p class="rating-display">⭐ <?php echo $avgRating; ?> (<?php echo $ratingCount; ?> reviews)</p>
            <?php } else { ?>
              <p class="rating-display no-rating">No ratings yet</p>
            <?php } ?>
            <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; margin-bottom:4px;">Can teach:</p>
            <div class="skill-tags-row"><?php echo skillTags($row['teach_skill']); ?></div>
            <p style="font-size:0.82rem; color:var(--text-muted); font-weight:600; margin-bottom:4px;">Wants to learn:</p>
            <div class="skill-tags-row"><?php echo skillTags($row['learn_skill']); ?></div>
            <?php if ($showContact) { ?>
              <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['mobile']); ?></p>
            <?php } else { ?>
              <p class="locked-contact">🔒 Contact visible after connection is accepted</p>
            <?php } ?>

            <?php if ($row['name'] == $currentUser) { ?>
              <p style="text-align:center; font-weight:600; color:var(--accent); margin-top:10px;">This is your profile</p>
            <?php } elseif ($myRequestStatus == 'pending') { ?>
              <p class="status-label status-pending">Request Pending</p>
            <?php } elseif ($myRequestStatus == 'accepted') { ?>
              <p class="status-label status-accepted">✓ Connected</p>
              <a href="messages.php?with=<?php echo urlencode($row['name']); ?>" class="connectBtn" style="display:block; text-align:center; text-decoration:none; margin-top:8px;">Message</a>
            <?php } else { ?>
              <form action="connect.php" method="POST" class="connect-form ajax-form">
                <?php echo csrf_field(); ?>
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
          <p><?php echo ($search != "" || $branch != "") ? "Try a different search or branch." : "Be the first to create a profile!"; ?></p>
        </div>
      <?php } ?>
    </div>
<?php layoutEnd(); ?>