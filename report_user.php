<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Report Profile", "Log in to submit a profile report.");
}

$target_id = intval($_GET['id'] ?? 0);
$target_name = $_GET['name'] ?? '';

layoutStart("Report " . $target_name, "Report " . $target_name, "Let us know if something is wrong with this profile.");
?>
    <form action="save_report.php" method="POST" class="ajax-form">
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
<?php layoutEnd(); ?>