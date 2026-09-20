<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['profileName'])) {
  header("Location: login.php");
  exit;
}

$target_id = intval($_GET['id']);
$target_name = $_GET['name'];
$currentUser = $_SESSION['profileName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rate <?php echo htmlspecialchars($target_name); ?> - Skill Connect</title>
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
    <h1>Rate <?php echo htmlspecialchars($target_name); ?></h1>
    <p>Share your experience learning from them.</p>
  </header>
  <main>
    <form action="save_rating.php" method="POST">
      <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
      <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($target_name); ?>">
      <div class="form-group">
        <label for="rating">Rating</label>
        <select id="rating" name="rating" required>
          <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
          <option value="4">⭐⭐⭐⭐ Good</option>
          <option value="3">⭐⭐⭐ Average</option>
          <option value="2">⭐⭐ Below Average</option>
          <option value="1">⭐ Poor</option>
        </select>
      </div>
      <div class="form-group">
        <label for="comment">Comment (optional)</label>
        <input type="text" id="comment" name="comment" placeholder="How was your experience?">
      </div>
      <button type="submit">Submit Rating</button>
    </form>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
</body>
</html>