<?php
// report_user.php
session_start();
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$target_id = intval($_GET['id']);
$target_name = $_GET['name'];
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report <?php echo htmlspecialchars($target_name); ?> - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="<?php echo htmlspecialchars($_SESSION['profileName']); ?>">
  <nav>
    <a href="index.php">Home</a>
    <a href="profile.php">My Profile</a>
    <a href="browse.php">Browse Skills</a>
  </nav>
  <header>
    <h1>Report <?php echo htmlspecialchars($target_name); ?></h1>
    <p>Let us know if something's wrong with this profile.</p>
  </header>
  <main>
    <form action="save_report.php" method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
      <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($target_name); ?>">
      <div class="form-group">
        <label for="reason">Reason</label>
        <select id="reason" name="reason" required>
          <option value="Spam">Spam</option>
          <option value="Inappropriate behavior">Inappropriate behavior</option>
          <option value="Fake profile">Fake profile</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label for="details">Details (optional)</label>
        <input type="text" id="details" name="details" placeholder="Tell us more...">
      </div>
      <button type="submit">Submit Report</button>
    </form>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
</body>
</html>