<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';
include 'helpers.php';

$categories = announcementCategories();
$list = $conn->query("SELECT a.*, DATEDIFF(a.deadline, CURDATE()) AS days_left, (SELECT COUNT(*) FROM announcement_interest i WHERE i.announcement_id = a.id) AS interested FROM announcements a ORDER BY a.pinned DESC, a.created_at DESC, a.id DESC");

layoutStart("Manage Announcements", "Manage Announcements", "Post notices that every student can see.", "admin");
?>
    <form id="announceForm" class="ajax-form" action="admin_save_announcement.php" method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="200" required placeholder="e.g. TCS campus drive on 20 October">
      </div>
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" required>
          <?php foreach ($categories as $cat) { ?><option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option><?php } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="body">Details</label>
        <textarea id="body" name="body" maxlength="5000" required placeholder="Eligibility, venue, how to apply..."></textarea>
      </div>
      <div class="form-group">
        <label for="link">Link (optional)</label>
        <input type="url" id="link" name="link" placeholder="https://...">
      </div>
      <div class="form-group">
        <label for="deadline">Deadline (optional)</label>
        <input type="date" id="deadline" name="deadline">
      </div>
      <label class="check-row"><input type="checkbox" name="pinned" value="1"> Pin to the top</label>
      <label class="check-row"><input type="checkbox" name="notify" value="1"> Also email all verified students</label>
      <button type="submit">Post announcement</button>
    </form>

    <h2 class="section-title">Posted announcements (<?php echo $list->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>Title</th><th>Category</th><th>Deadline</th><th>Interested</th><th>Posted</th><th>Actions</th></tr>
        <?php while ($a = $list->fetch_assoc()) { $info = deadlineInfo($a['days_left']); ?>
          <tr>
            <td><?php echo $a['pinned'] ? '📌 ' : ''; ?><?php echo h($a['title']); ?></td>
            <td><?php echo h($a['category']); ?></td>
            <td><?php echo $a['deadline'] ? h($a['deadline']) . ' (' . h($info[0]) . ')' : '—'; ?></td>
            <td><?php echo $a['interested']; ?></td>
            <td><?php echo timeAgo($a['created_at']); ?></td>
            <td class="action-cell">
              <form class="ajax-form inline-form" action="admin_announcement_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="action" value="<?php echo $a['pinned'] ? 'unpin' : 'pin'; ?>">
                <button type="submit" class="admin-mini-btn"><?php echo $a['pinned'] ? 'Unpin' : 'Pin'; ?></button>
              </form>
              <form class="ajax-form inline-form" action="admin_announcement_action.php" method="POST" onsubmit="return confirm('Delete this announcement?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="admin-delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </table>
    </div>
<?php layoutEnd('admin'); ?>
