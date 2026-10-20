<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_valid()) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        get_db()->prepare('DELETE FROM typing_records WHERE id = ?')->execute([$id]);
    }
    header('Location: ' . base_url('admin/typing-results.php'));
    exit;
}

$db = get_db();

$stats = $db->query(
    'SELECT COUNT(*) AS total, MAX(wpm) AS best, ROUND(AVG(wpm)) AS avg_wpm, ROUND(AVG(accuracy)) AS avg_acc
     FROM typing_records'
)->fetch();

$records = $db->query(
    "SELECT r.*, k.name AS kb_name, k.model AS kb_model
     FROM typing_records r
     LEFT JOIN keyboards k ON k.id = r.keyboard_id
     ORDER BY r.created_at DESC, r.id DESC"
)->fetchAll();

$page_title = 'Typing Test Results — ' . SITE_TITLE;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>All typing test results</h1>
    <p>Every run logged across the whole site, newest first.</p>
  </div>
  <a href="<?= base_url('typing-test.php') ?>" class="btn btn-sm">&larr; Back to typing test</a>
</div>

<?php if ((int)$stats['total'] > 0): ?>
  <div class="tt-stats" style="justify-content:flex-start;gap:48px;margin:6px 0 30px;">
    <div class="tt-stat"><b><?= (int)$stats['total'] ?></b><small>total runs logged</small></div>
    <div class="tt-stat"><b><?= (int)$stats['best'] ?></b><small>best wpm</small></div>
    <div class="tt-stat"><b><?= (int)$stats['avg_wpm'] ?></b><small>average wpm</small></div>
    <div class="tt-stat"><b><?= (int)$stats['avg_acc'] ?>%</b><small>average accuracy</small></div>
  </div>
  <style>
    .tt-stat b{ display:block; font-size:2rem; font-weight:700; font-family:"JetBrains Mono",monospace; color:var(--accent); line-height:1; }
    .tt-stat small{ color:var(--keycap-faint); font-size:.8rem; }
  </style>
<?php endif; ?>

<?php if (!$records): ?>
  <div class="empty-state">
    <h2>No results yet</h2>
    <p>Nobody's run the typing test yet — or every run has been deleted.</p>
    <a class="btn btn-accent" href="<?= base_url('typing-test.php') ?>">Take the test</a>
  </div>
<?php else: ?>
  <div class="compare-table-wrap">
    <table class="compare-table">
      <thead>
        <tr><th>Date</th><th>Length</th><th>WPM</th><th>Raw</th><th>Accuracy</th><th>Keyboard</th><th>Switches</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r):
          $switchesShown = $r['keyboard_id'] ? $r['switches_snapshot'] : $r['guest_switches'];
        ?>
          <tr>
            <td><?= h(date('M j, Y g:i A', strtotime($r['created_at']))) ?></td>
            <td><?= (int)$r['duration'] ?>s</td>
            <td><?= (int)$r['wpm'] ?></td>
            <td><?= (int)$r['raw_wpm'] ?></td>
            <td><?= (int)$r['accuracy'] ?>%</td>
            <td>
              <?php if ($r['keyboard_id'] && $r['kb_name']): ?>
                <a href="<?= base_url('keyboard.php?id=' . $r['keyboard_id']) ?>"><?= h($r['kb_name']) ?></a><?= $r['kb_model'] ? ' <span class="mono">' . h($r['kb_model']) . '</span>' : '' ?>
              <?php elseif ($r['guest_model']): ?>
                <?= h($r['guest_model']) ?> <span class="mini-tag">guest</span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $switchesShown ? h($switchesShown) : '—' ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Delete this result?');" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
