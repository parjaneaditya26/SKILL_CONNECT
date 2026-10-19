<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';

if (!isset($_SESSION['profileName'])) {
  renderLoginRequired("Help Board", "Log in to find teammates and get help.");
}

$currentUser = $_SESSION['profileName'];
$types = ["teammates" => "Looking for teammates", "help" => "Needs help"];
$filter = isset($_GET['type']) ? $_GET['type'] : '';
if (!isset($types[$filter])) { $filter = ''; }

$base = "SELECT p.*, u.id AS author_id, u.branch, u.year_of_study,
  (SELECT COUNT(*) FROM help_responses r WHERE r.post_id = p.id) AS resp_count
  FROM help_posts p LEFT JOIN users u ON u.name = p.author_name";

if ($filter !== '') {
  $stmt = $conn->prepare($base . " WHERE p.type = ? ORDER BY (p.status = 'open') DESC, p.created_at DESC, p.id DESC LIMIT 100");
  $stmt->bind_param("s", $filter);
} else {
  $stmt = $conn->prepare($base . " ORDER BY (p.status = 'open') DESC, p.created_at DESC, p.id DESC LIMIT 100");
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$responded = [];
$rs = $conn->prepare("SELECT post_id FROM help_responses WHERE responder_name = ?");
$rs->bind_param("s", $currentUser);
$rs->execute();
$rr = $rs->get_result();
while ($r = $rr->fetch_assoc()) { $responded[$r['post_id']] = true; }
$rs->close();

layoutStart("Help Board", "Help Board", "Find teammates for projects and hackathons, or ask your classmates for help.");
?>
    <details class="new-post">
      <summary class="connectBtn">＋ New post</summary>
      <form id="helpForm" class="ajax-form" action="save_help_post.php" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label for="type">What do you need?</label>
          <select id="type" name="type" required>
            <?php foreach ($types as $key => $label) { ?><option value="<?php echo $key; ?>"><?php echo h($label); ?></option><?php } ?>
          </select>
        </div>
        <div class="form-group">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" maxlength="150" required placeholder="e.g. Need 2 teammates for the college hackathon">
        </div>
        <div class="form-group">
          <label for="details">Details</label>
          <textarea id="details" name="details" maxlength="1000" required placeholder="What are you working on, what skills do you need, when?"></textarea>
        </div>
        <button type="submit">Post</button>
      </form>
    </details>

    <div class="chips">
      <a href="help_board.php" class="chip <?php echo $filter === '' ? 'active' : ''; ?>">All</a>
      <?php foreach ($types as $key => $label) { ?>
        <a href="help_board.php?type=<?php echo $key; ?>" class="chip <?php echo $filter === $key ? 'active' : ''; ?>"><?php echo h($label); ?></a>
      <?php } ?>
    </div>

    <?php if (count($posts) > 0) { ?>
      <div class="help-grid">
        <?php foreach ($posts as $p) {
          $isAuthor = ($p['author_name'] === $currentUser);
          $isOpen = ($p['status'] === 'open');
          $responses = [];
          if ($isAuthor && $p['resp_count'] > 0) {
            $q = $conn->prepare("SELECT r.*, u.id AS uid, u.branch, u.year_of_study FROM help_responses r LEFT JOIN users u ON u.name = r.responder_name WHERE r.post_id = ? ORDER BY r.created_at ASC");
            $q->bind_param("i", $p['id']);
            $q->execute();
            $responses = $q->get_result()->fetch_all(MYSQLI_ASSOC);
            $q->close();
          }
        ?>
          <div class="help-card <?php echo $isOpen ? '' : 'is-done'; ?>">
            <div class="announce-top">
              <span class="help-type-tag type-<?php echo h($p['type']); ?>"><?php echo h($types[$p['type']] ?? $p['type']); ?></span>
              <span class="help-status <?php echo $isOpen ? '' : 'done'; ?>"><?php echo $isOpen ? 'Open' : 'Done'; ?></span>
            </div>
            <h3><?php echo h($p['title']); ?></h3>
            <p class="announce-body"><?php echo nl2br(h($p['details'])); ?></p>
            <p class="announce-meta">
              Posted by
              <?php if ($p['author_id']) { ?><a href="profile_view.php?id=<?php echo $p['author_id']; ?>"><?php echo h($p['author_name']); ?></a><?php } else { echo h($p['author_name']); } ?>
              <?php if ($p['branch']) { ?> · <?php echo h($p['branch']); ?><?php } ?>
              <?php if ($p['year_of_study']) { ?> · <?php echo h($p['year_of_study']); ?><?php } ?>
              · <?php echo timeAgo($p['created_at']); ?>
              · <?php echo $p['resp_count']; ?> responded
            </p>

            <div class="card-actions">
              <?php if ($isAuthor) { ?>
                <form class="ajax-form inline-form" action="help_post_action.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <input type="hidden" name="action" value="<?php echo $isOpen ? 'done' : 'reopen'; ?>">
                  <button type="submit" class="btn-outline"><?php echo $isOpen ? 'Mark as done' : 'Reopen'; ?></button>
                </form>
                <form class="ajax-form inline-form" action="help_post_action.php" method="POST" onsubmit="return confirm('Delete this post?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn-outline danger">Delete</button>
                </form>
              <?php } elseif (!$isOpen) { ?>
                <span class="announce-meta">This post is closed.</span>
              <?php } elseif (isset($responded[$p['id']])) { ?>
                <span class="status-label status-accepted" style="margin:0;">✓ You responded</span>
              <?php } else { ?>
                <details class="respond-box">
                  <summary class="btn-outline"><?php echo $p['type'] === 'teammates' ? "I'm interested" : "I can help"; ?></summary>
                  <form class="ajax-form inline-form" action="respond_help_post.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                    <input type="text" name="message" maxlength="300" required placeholder="Say a few words about how you can help">
                    <button type="submit">Send</button>
                  </form>
                </details>
              <?php } ?>
            </div>

            <?php if ($isAuthor && count($responses) > 0) { ?>
              <div class="response-list">
                <p class="announce-meta" style="margin-bottom:6px;"><strong>Responses</strong></p>
                <?php foreach ($responses as $r) { $st = connectionStatus($conn, $currentUser, $r['responder_name']); ?>
                  <div class="response-item">
                    <strong><?php if ($r['uid']) { ?><a href="profile_view.php?id=<?php echo $r['uid']; ?>"><?php echo h($r['responder_name']); ?></a><?php } else { echo h($r['responder_name']); } ?></strong>
                    <?php if ($r['branch']) { ?><span class="announce-meta"> · <?php echo h($r['branch']); ?></span><?php } ?>
                    <?php if ($r['year_of_study']) { ?><span class="announce-meta"> · <?php echo h($r['year_of_study']); ?></span><?php } ?>
                    <div><?php echo h($r['message']); ?></div>
                    <div class="response-actions">
                      <?php if ($st === 'accepted') { ?>
                        <a class="btn-outline" href="messages.php?with=<?php echo urlencode($r['responder_name']); ?>">Message</a>
                      <?php } elseif ($st === 'pending') { ?>
                        <span class="announce-meta">Connection request pending</span>
                      <?php } elseif ($r['uid']) { ?>
                        <form class="ajax-form inline-form" action="connect.php" method="POST">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="target_id" value="<?php echo $r['uid']; ?>">
                          <input type="hidden" name="target_name" value="<?php echo h($r['responder_name']); ?>">
                          <button type="submit" class="btn-outline">Connect</button>
                        </form>
                      <?php } ?>
                    </div>
                  </div>
                <?php } ?>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-state">
        <h3>No posts yet</h3>
        <p>Be the first to post. Ask for teammates or a hand with something.</p>
      </div>
    <?php } ?>
<?php layoutEnd(); ?>
