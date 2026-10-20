<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$all = all_keyboards('name ASC');

// Selected IDs come from repeated ?ids[]=1&ids[]=2... query params.
$selectedIds = array_values(array_filter(array_map('intval', $_GET['ids'] ?? [])));
$selectedIds = array_slice(array_unique($selectedIds), 0, 4);

$selected = [];
foreach ($selectedIds as $sid) {
    $kb = find_keyboard($sid);
    if ($kb) $selected[] = $kb;
}

$page_title = 'Compare — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Compare builds</h1>
    <p>Pick up to four keyboards to line up spec-by-spec.</p>
  </div>
</div>

<div class="compare-picker">
  <form method="get">
    <?php for ($slot = 0; $slot < 4; $slot++): ?>
      <div class="field">
        <label for="slot<?= $slot ?>">Keyboard <?= $slot + 1 ?></label>
        <select id="slot<?= $slot ?>" name="ids[]">
          <option value="">— none —</option>
          <?php foreach ($all as $kb): ?>
            <option value="<?= $kb['id'] ?>" <?= (isset($selectedIds[$slot]) && $selectedIds[$slot] == $kb['id']) ? 'selected' : '' ?>>
              <?= h($kb['name']) ?><?= $kb['model'] ? ' — ' . h($kb['model']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endfor; ?>
    <button class="btn btn-accent" type="submit">Compare</button>
  </form>
</div>

<?php if (count($selected) >= 2): ?>
  <div class="compare-table-wrap">
    <table class="compare-table">
      <thead>
        <tr>
          <th class="row-label">&nbsp;</th>
          <?php foreach ($selected as $kb): $src = photo_src($kb['photo']); ?>
            <th>
              <?php if ($src): ?><img class="kb-thumb" src="<?= h($src) ?>" alt="<?= h($kb['name']) ?>"><?php endif; ?>
              <a href="<?= base_url('keyboard.php?id=' . $kb['id']) ?>"><?= h($kb['name']) ?></a>
              <?php if ($kb['model']): ?><span class="model-line mono"><?= h($kb['model']) ?></span><?php endif; ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach (spec_fields() as [$col, $label]): ?>
          <tr>
            <th class="row-label"><?= h($label) ?></th>
            <?php foreach ($selected as $kb): ?>
              <td><?= $kb[$col] !== null && $kb[$col] !== '' ? h($kb[$col]) : '—' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        <tr>
          <th class="row-label">Formerly known as</th>
          <?php foreach ($selected as $kb):
              $f = $kb['formerly_known_as_id'] ? find_keyboard((int)$kb['formerly_known_as_id']) : null; ?>
            <td>
              <?php if ($f): ?><a href="<?= base_url('keyboard.php?id=' . $f['id']) ?>"><?= h($f['name']) ?></a><?php else: ?>—<?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
<?php elseif ($selected): ?>
  <div class="empty-state" style="margin-top:24px;">
    <h2>Pick at least two</h2>
    <p>Choose a second keyboard above to start comparing.</p>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
