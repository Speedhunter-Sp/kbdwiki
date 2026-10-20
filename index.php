<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!admin_account_exists()) {
    header('Location: ' . base_url('setup.php'));
    exit;
}

$q = trim($_GET['q'] ?? '');
$activeTag = trim($_GET['tag'] ?? '');
$hasFilter = $q !== '' || $activeTag !== '';

$totalCount = (int) get_db()->query('SELECT COUNT(*) AS c FROM keyboards')->fetch()['c'];
$keyboards = $hasFilter ? search_keyboards($q, $activeTag) : all_keyboards('name ASC');
$tags = all_tags();

$page_title = SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>The collection</h1>
    <p>Every keyboard Speedhunter has built and owned.</p>
  </div>
  <span class="count-pill"><?= $totalCount ?> keyboard<?= $totalCount === 1 ? '' : 's' ?></span>
</div>

<?php if ($totalCount > 0): ?>
  <form method="get" class="search-form">
    <?php if ($activeTag !== ''): ?><input type="hidden" name="tag" value="<?= h($activeTag) ?>"><?php endif; ?>
    <input type="text" name="q" placeholder="Search by name or model…" value="<?= h($q) ?>">
    <button class="btn btn-sm" type="submit">Search</button>
    <?php if ($hasFilter): ?><a class="btn btn-sm" href="<?= base_url('index.php') ?>">Clear filters</a><?php endif; ?>
  </form>

  <?php if ($tags): ?>
    <div class="tag-cloud">
      <?php foreach ($tags as $t):
          $isActive = strcasecmp($t['name'], $activeTag) === 0;
          $params = array_filter(['q' => $q, 'tag' => $isActive ? '' : $t['name']], fn($v) => $v !== '');
      ?>
        <a class="tag-pill <?= $isActive ? 'active' : '' ?>" href="<?= base_url('index.php?' . http_build_query($params)) ?>">
          <?= h($t['name']) ?> <span class="tag-count mono"><?= (int)$t['cnt'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if (!$totalCount): ?>
  <div class="empty-state">
    <h2>No keyboards yet</h2>
    <p>Nothing's been added to the wiki.</p>
    <?php if (is_logged_in()): ?>
      <p><a class="btn btn-accent" href="<?= base_url('admin/add.php') ?>">Add your first keyboard</a></p>
    <?php else: ?>
      <p><a class="btn" href="<?= base_url('login.php') ?>">Log in to add one</a></p>
    <?php endif; ?>
  </div>
<?php elseif (!$keyboards): ?>
  <div class="empty-state">
    <h2>No matches</h2>
    <p>Nothing fits that search or tag.</p>
    <a class="btn" href="<?= base_url('index.php') ?>">Clear filters</a>
  </div>
<?php else: ?>
  <div class="keycap-grid">
    <?php foreach ($keyboards as $kb): $kbTags = tags_for_keyboard($kb['id']); ?>
      <a class="keycap-card" href="<?= base_url('keyboard.php?id=' . $kb['id']) ?>">
        <div class="thumb" <?php $src = photo_src($kb['photo']); if ($src): ?>style="background-image:url('<?= h($src) ?>')"<?php endif; ?>>
          <?php if (!$src): ?>no photo<?php endif; ?>
        </div>
        <div class="info">
          <p class="kb-name"><?= h($kb['name']) ?></p>
          <?php if ($kb['model']): ?><p class="kb-model mono"><?= h($kb['model']) ?></p><?php endif; ?>
          <?php if ($kbTags): ?>
            <p class="kb-card-tags">
              <?php foreach (array_slice($kbTags, 0, 3) as $t): ?><span class="mini-tag"><?= h($t['name']) ?></span><?php endforeach; ?>
            </p>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
