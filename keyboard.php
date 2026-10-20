<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$kb = $id ? find_keyboard($id) : null;

if (!$kb) {
    http_response_code(404);
    $page_title = 'Not found — ' . SITE_TITLE;
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state"><h2>Keyboard not found</h2><p>It may have been deleted.</p>'
       . '<a class="btn" href="' . base_url('index.php') . '">Back to the collection</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$formerly = $kb['formerly_known_as_id'] ? find_keyboard((int)$kb['formerly_known_as_id']) : null;
$src = photo_src($kb['photo']);
$tags = tags_for_keyboard($kb['id']);
$gallery = gallery_for_keyboard($kb['id']);

$page_title = $kb['name'] . ' — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<a class="breadcrumb mono" href="<?= base_url('index.php') ?>">&larr; back to collection</a>

<?php if ($src): ?>
  <div class="kb-hero"><img src="<?= h($src) ?>" alt="<?= h($kb['name']) ?>"></div>
<?php endif; ?>

<div class="kb-titleblock">
  <h1><?= h($kb['name']) ?></h1>
  <?php if ($kb['model']): ?><p class="model-line"><?= h($kb['model']) ?></p><?php endif; ?>
  <?php if ($tags): ?>
    <div class="tag-row">
      <?php foreach ($tags as $t): ?>
        <a class="tag-pill" href="<?= base_url('index.php?tag=' . rawurlencode($t['name'])) ?>"><?= h($t['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="spec-block">
  <div class="spec-block-head">SPECIFICATIONS</div>
  <div class="spec-grid">
    <?php foreach (spec_fields() as [$col, $label]): ?>
      <div class="spec-item">
        <span class="label mono"><?= h($label) ?></span>
        <span class="value <?= $kb[$col] === null || $kb[$col] === '' ? 'empty' : '' ?>">
          <?= $kb[$col] !== null && $kb[$col] !== '' ? h($kb[$col]) : '—' ?>
        </span>
      </div>
    <?php endforeach; ?>
    <div class="spec-item">
      <span class="label mono">Formerly known as</span>
      <span class="value <?= $formerly ? '' : 'empty' ?>">
        <?php if ($formerly): ?>
          <a href="<?= base_url('keyboard.php?id=' . $formerly['id']) ?>"><?= h($formerly['name']) ?><?= $formerly['model'] ? ' (' . h($formerly['model']) . ')' : '' ?></a>
        <?php else: ?>—<?php endif; ?>
      </span>
    </div>
  </div>
</div>

<?php if ($kb['intro_paragraph']): ?>
  <p class="intro-paragraph"><?= render_body_text($kb['intro_paragraph']) ?></p>
<?php endif; ?>

<?php if ($gallery): ?>
  <section class="wiki-section">
    <h2>Gallery</h2>
    <div class="gallery-grid">
      <?php foreach ($gallery as $p): $gsrc = photo_src($p['filename']); if (!$gsrc) continue; ?>
        <button type="button" class="gallery-thumb-btn" data-full="<?= h($gsrc) ?>" aria-label="View larger photo">
          <img src="<?= h($gsrc) ?>" alt="<?= h($kb['name']) ?> detail photo" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php
$sections = [
    'description'         => 'Description',
    'build_timeline'      => 'Build timeline',
    'personal_review'     => 'Personal review',
    'future_build_plans'  => 'Future build plans',
];
foreach ($sections as $col => $label):
?>
  <section class="wiki-section">
    <h2><?= h($label) ?></h2>
    <div class="body-text <?= $kb[$col] ? '' : 'is-empty' ?>"><?= $kb[$col] ? render_body_text($kb[$col]) : '' ?></div>
  </section>
<?php endforeach; ?>


<?php if (is_logged_in()): ?>
  <div class="kb-actions">
    <a class="btn" href="<?= base_url('admin/edit.php?id=' . $kb['id']) ?>">Edit</a>
    <a class="btn btn-danger" href="<?= base_url('admin/delete.php?id=' . $kb['id']) ?>">Delete</a>
  </div>
<?php endif; ?>

<?php if ($gallery): ?>
  <div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
    <img id="lightboxImg" src="" alt="">
  </div>
  <script>
    (function () {
      var box = document.getElementById('lightbox');
      var img = document.getElementById('lightboxImg');
      document.querySelectorAll('.gallery-thumb-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          img.src = btn.getAttribute('data-full');
          box.hidden = false;
        });
      });
      function close() { box.hidden = true; img.src = ''; }
      document.getElementById('lightboxClose').addEventListener('click', close);
      box.addEventListener('click', function (e) { if (e.target === box) close(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
  </script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
