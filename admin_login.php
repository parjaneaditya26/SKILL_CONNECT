<?php
session_start();
include "db_connect.php";
include "helpers.php";

$error = "";
$ADMIN_PASSWORD = "skillconnect2026";

if (!empty($_SESSION["isAdmin"])) {
  header("Location: admin.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $pass = trim($_POST["password"] ?? "");
  if ($pass === $ADMIN_PASSWORD) {
    $_SESSION["isAdmin"] = true;
    header("Location: admin.php");
    exit;
  } else {
    $error = "Incorrect admin password. Please try again.";
  }
}

layoutStart("Admin Login", "Admin Login", "Enter the administrator password to access the Admin Control Center.");
?>
    <div style="max-width:420px; margin:28px auto 48px; background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:28px; box-shadow:0 8px 24px rgba(0,0,0,0.06);">
      <div style="text-align:center; margin-bottom:20px;">
        <span style="display:inline-block; width:52px; height:52px; line-height:52px; border-radius:50%; background:var(--accent-light); font-size:1.6rem; margin-bottom:8px;">🛡️</span>
        <h2 style="margin:0 0 4px; font-size:1.35rem;">Admin Password Login</h2>
        <p style="margin:0; font-size:0.85rem; color:var(--text-muted);">Enter your administrator password to continue.</p>
      </div>

      <?php if ($error) { ?>
        <div style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:0.88rem; font-weight:600;">
          ⚠️ <?php echo h($error); ?>
        </div>
      <?php } ?>

      <form action="admin_login.php" method="POST" style="margin:0; max-width:none; padding:0; border:none; box-shadow:none;">
        <div class="form-group">
          <label for="password">Admin Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="Enter admin password..." required autofocus>
            <button type="button" class="toggle-password" data-target="password">👁</button>
          </div>
        </div>

        <button type="submit" style="width:100%; padding:12px; font-size:0.95rem; font-weight:600; margin-top:8px;">Unlock Admin Dashboard →</button>
      </form>
      <div style="text-align:center; margin-top:16px;">
        <a href="index.php?view_site=1" style="font-size:0.85rem; color:var(--text-muted); text-decoration:none;">← Back to Home Page</a>
      </div>
    </div>
<?php layoutEnd(); ?>
