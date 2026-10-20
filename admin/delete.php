<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$kb = find_keyboard($id);
if (!$kb) {
    header('Location: ' . base_url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && csrf_valid()) {
        // Detach anything that pointed to this build as "formerly known as".
        $stmt = get_db()->prepare('UPDATE keyboards SET formerly_known_as_id = NULL WHERE formerly_known_as_id = ?');
        $stmt->execute([$id]);

        if ($kb['photo'] && file_exists(UPLOAD_DIR . $kb['photo'])) {
            @unlink(UPLOAD_DIR . $kb['photo']);
        }
        foreach (gallery_for_keyboard($id) as $p) {
            if ($p['filename'] && file_exists(UPLOAD_DIR . $p['filename'])) {
                @unlink(UPLOAD_DIR . $p['filename']);
            }
        }
        // keyboard_photos and keyboard_tags rows cascade automatically via FK.
        $stmt = get_db()->prepare('DELETE FROM keyboards WHERE id = ?');
        $stmt->execute([$id]);
    }
    header('Location: ' . base_url('index.php'));
    exit;
}

$page_title = 'Delete ' . $kb['name'] . ' — ' . SITE_TITLE;
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>Delete <?= h($kb['name']) ?>?</h1></div></div>
<div class="form-card">
  <p style="color:var(--keycap-dim);max-width:56ch;">
    This permanently removes <strong style="color:var(--keycap);"><?= h($kb['name']) ?></strong>
    <?= $kb['model'] ? '(' . h($kb['model']) . ')' : '' ?> and its photo. This can't be undone.
    Any build that lists it as &ldquo;formerly known as&rdquo; will just show &mdash; instead.
  </p>
  <form method="post" class="form-actions" style="justify-content:flex-start;">
    <?= csrf_field() ?>
    <a class="btn" href="<?= base_url('keyboard.php?id=' . $kb['id']) ?>">Cancel</a>
    <button class="btn btn-danger" type="submit" name="confirm" value="1">Yes, delete it</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
