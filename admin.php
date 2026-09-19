<?php
include 'db_connect.php';

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$requests = $conn->query("SELECT * FROM requests ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Skill Connect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav>
    <a href="index.php">Home</a>
    <a href="profile.html">My Profile</a>
    <a href="browse.php">Browse Skills</a>
    <a href="requests.php">My Requests</a>
    <a href="admin.php">Admin</a>
  </nav>
  <header>
    <h1>Admin Dashboard</h1>
    <p>Overview of all users and requests.</p>
  </header>
  <main style="max-width: 1000px; margin: 0 auto; text-align: left;">
    <h2 style="color:#14323b; margin-bottom:16px;">All Users (<?php echo $users->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>ID</th><th>Name</th><th>Mobile</th><th>Teaches</th><th>Wants to Learn</th><th>Category</th><th>Joined</th></tr>
        <?php while ($u = $users->fetch_assoc()) { ?>
          <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo $u['name']; ?></td>
            <td><?php echo $u['mobile']; ?></td>
            <td><?php echo $u['teach_skill']; ?></td>
            <td><?php echo $u['learn_skill']; ?></td>
            <td><?php echo $u['category']; ?></td>
            <td><?php echo $u['created_at']; ?></td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <h2 style="color:#14323b; margin:30px 0 16px;">All Requests (<?php echo $requests->num_rows; ?>)</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <tr><th>ID</th><th>From</th><th>To</th><th>Status</th><th>Date</th></tr>
        <?php while ($r = $requests->fetch_assoc()) { ?>
          <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo $r['requester_name']; ?></td>
            <td><?php echo $r['target_name']; ?></td>
            <td><?php echo ucfirst($r['status']); ?></td>
            <td><?php echo $r['created_at']; ?></td>
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