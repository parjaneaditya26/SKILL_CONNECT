<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Rate Mentor", "Log in to leave a rating.");
}

$target_id = intval($_GET['id'] ?? 0);
$target_name = $_GET['name'] ?? '';

layoutStart("Rate " . $target_name, "Rate " . $target_name, "Share your experience learning from them.");
?>
    <form action="save_rating.php" method="POST" class="ajax-form">
      <?php echo csrf_field(); ?>
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
<?php layoutEnd(); ?>