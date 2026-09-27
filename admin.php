<?php
session_start();
if (!isset($_SESSION['isAdmin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db_connect.php';

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$requests = $conn->query("SELECT * FROM requests ORDER BY id DESC");
$reports = $conn->query("SELECT * FROM reports ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-user="">
  <nav>
    <a href="index.php">Home</a>
    <a href="admin_logout.php">Admin Logout</a>
  </nav>
  <header>
    <h1>Admin Dashboard</h1>
    <p>Overview of all users and requests. Admin can remove any entry.</p>
  </header>
  <main style="max-width: 1100px; margin: 0 auto; text-align: left;">
    <p style="text-align:center; margin-bottom:20px;">
      <a href="export_users.php" style="color:#1d4ed8; font-weight:600; margin-right:20px;">⬇ Export Users (CSV)</a>
      <a href="export_requests.php" style="color:#1d4ed8; font-weight:600;">⬇ Export Requests (CSV)</a>
    </p>

    <h2 style="color:#14323b; margin-bottom:16px;">All Users (<?php echo $users->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Teaches</th><th>Wants to Learn</th><th>Category</th><th>Verified</th><th>Joined</th><th>Action</th></tr>
        <?php while ($u = $users->fetch_assoc()) { ?>
          <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['mobile']); ?></td>
            <td><?php echo htmlspecialchars($u['teach_skill']); ?></td>
            <td><?php echo htmlspecialchars($u['learn_skill']); ?></td>
            <td><?php echo htmlspecialchars($u['category']); ?></td>
            <td><?php echo $u['verified'] ? 'Yes' : 'No'; ?></td>
            <td><?php echo $u['created_at']; ?></td>
            <td>
              <form action="admin_delete_user.php" method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.');" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                <button type="submit" class="admin-delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <h2 style="color:#14323b; margin:30px 0 16px;">All Requests (<?php echo $requests->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>ID</th><th>From</th><th>To</th><th>Status</th><th>Date</th><th>Action</th></tr>
        <?php while ($r = $requests->fetch_assoc()) { ?>
          <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['requester_name']); ?></td>
            <td><?php echo htmlspecialchars($r['target_name']); ?></td>
            <td><?php echo ucfirst($r['status']); ?></td>
            <td><?php echo $r['created_at']; ?></td>
            <td>
              <form action="admin_delete_request.php" method="POST" onsubmit="return confirm('Delete this request?');" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="admin-delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <h2 style="color:#14323b; margin:30px 0 16px;">Reports (<?php echo $reports->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>ID</th><th>Reporter</th><th>Target</th><th>Reason</th><th>Details</th><th>Date</th><th>Action</th></tr>
        <?php while ($rp = $reports->fetch_assoc()) { ?>
          <tr>
            <td><?php echo $rp['id']; ?></td>
            <td><?php echo htmlspecialchars($rp['reporter_name']); ?></td>
            <td><?php echo htmlspecialchars($rp['target_name']); ?></td>
            <td><?php echo htmlspecialchars($rp['reason']); ?></td>
            <td><?php echo htmlspecialchars($rp['details']); ?></td>
            <td><?php echo $rp['created_at']; ?></td>
            <td>
              <form action="admin_delete_report.php" method="POST" onsubmit="return confirm('Dismiss this report?');" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo $rp['id']; ?>">
                <button type="submit" class="admin-delete-btn">Dismiss</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </table>
    </div>
  </main>
  <footer>
    <p>Skill Connect &copy; 2026 — A College Project (CEP)</p>
  </footer>
</body>
</html>