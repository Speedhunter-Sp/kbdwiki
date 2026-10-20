<?php
// Expects: $kb (array, may have empty-string defaults), $allKeyboards (for the
// "formerly known as" dropdown, excluding the current record), $error (string|null),
// $tagsCsv (string, comma-separated existing tags), $galleryPhotos (array, existing
// gallery rows — empty on the add form).
$kb = $kb ?? [];
$tagsCsv = $tagsCsv ?? '';
$galleryPhotos = $galleryPhotos ?? [];
$get = fn($col) => h($kb[$col] ?? '');
?>
<?php if (!empty($error)): ?><div class="flash"><?= h($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-grid">
  <?= csrf_field() ?>

  <div class="field full">
    <label for="name">Keyboard name</label>
    <input type="text" id="name" name="name" placeholder="e.g. Project Darling" required value="<?= $get('name') ?>">
  </div>

  <div class="field full">
    <label for="photo">Main photo</label>
    <?php if (!empty($kb['photo'])):
        $src = photo_src($kb['photo']); ?>
      <div class="current-photo">
        <?php if ($src): ?><img src="<?= h($src) ?>" alt=""><?php endif; ?>
        <span class="hint">Current photo — upload a new one to replace it.</span>
      </div>
    <?php endif; ?>
    <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp,.gif">
    <span class="hint">JPG, PNG, WEBP or GIF, up to 8&nbsp;MB.</span>
  </div>

  <div class="field full">
    <label for="tags">Tags</label>
    <input type="text" id="tags" name="tags" placeholder="e.g. 65%, tri-mode, gasket mount" value="<?= h($tagsCsv) ?>">
    <span class="hint">Comma-separated. New tags are created automatically — use these to search/filter your collection later.</span>
  </div>

  <?php if ($galleryPhotos): ?>
    <div class="field full">
      <label>Existing gallery photos</label>
      <div class="gallery-manage">
        <?php foreach ($galleryPhotos as $p): $gsrc = photo_src($p['filename']); ?>
          <label class="gallery-manage-item">
            <img src="<?= h($gsrc) ?>" alt="">
            <span class="del-row"><input type="checkbox" name="delete_photo[]" value="<?= $p['id'] ?>"> Remove</span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="field full">
    <label for="gallery">Add gallery photos</label>
    <input type="file" id="gallery" name="gallery[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
    <span class="hint">Select multiple files for extra angles — case, plate, underside, switches, etc.</span>
  </div>

  <div class="form-section-title">Specifications</div>

  <div class="field">
    <label for="model">Model</label>
    <input type="text" id="model" name="model" placeholder="e.g. TM680" value="<?= $get('model') ?>">
  </div>
  <div class="field">
    <label for="layout">Layout</label>
    <input type="text" id="layout" name="layout" placeholder="e.g. 65%" value="<?= $get('layout') ?>">
  </div>
  <div class="field">
    <label for="frame_color">Frame color</label>
    <input type="text" id="frame_color" name="frame_color" value="<?= $get('frame_color') ?>">
  </div>
  <div class="field">
    <label for="frame_swap">Frame swap</label>
    <input type="text" id="frame_swap" name="frame_swap" placeholder="If applicable" value="<?= $get('frame_swap') ?>">
  </div>
  <div class="field">
    <label for="plate_type">Plate type</label>
    <input type="text" id="plate_type" name="plate_type" placeholder="e.g. FR4, PC, aluminum" value="<?= $get('plate_type') ?>">
  </div>
  <div class="field">
    <label for="pcb_type">PCB type</label>
    <input type="text" id="pcb_type" name="pcb_type" placeholder="e.g. North-facing" value="<?= $get('pcb_type') ?>">
  </div>
  <div class="field">
    <label for="mounting_style">Mounting style</label>
    <input type="text" id="mounting_style" name="mounting_style" placeholder="e.g. Gasket, tray, top mount" value="<?= $get('mounting_style') ?>">
  </div>
  <div class="field">
    <label for="switches">Switches</label>
    <input type="text" id="switches" name="switches" value="<?= $get('switches') ?>">
  </div>
  <div class="field">
    <label for="keycaps">Keycaps</label>
    <input type="text" id="keycaps" name="keycaps" placeholder="Profile / material" value="<?= $get('keycaps') ?>">
  </div>
  <div class="field">
    <label for="keycap_set">Keycap set</label>
    <input type="text" id="keycap_set" name="keycap_set" placeholder="e.g. GMK Oblivion" value="<?= $get('keycap_set') ?>">
  </div>
  <div class="field">
    <label for="connection_type">Connection type</label>
    <select id="connection_type" name="connection_type">
      <?php $conn = $kb['connection_type'] ?? '';
      foreach (['' => 'Not set', 'Wired' => 'Wired', 'Bluetooth' => 'Bluetooth', 'Tri-mode' => 'Tri-mode'] as $val => $lab): ?>
        <option value="<?= h($val) ?>" <?= $conn === $val ? 'selected' : '' ?>><?= h($lab) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="battery_size">Battery size</label>
    <input type="text" id="battery_size" name="battery_size" placeholder="If applicable" value="<?= $get('battery_size') ?>">
  </div>
  <div class="field">
    <label for="hotswap">Hotswap</label>
    <select id="hotswap" name="hotswap">
      <?php $hs = $kb['hotswap'] ?? '';
      foreach (['' => 'Not set', 'No' => 'No', 'Yes - 3-pin' => 'Yes — 3-pin', 'Yes - 5-pin' => 'Yes — 5-pin'] as $val => $lab): ?>
        <option value="<?= h($val) ?>" <?= $hs === $val ? 'selected' : '' ?>><?= h($lab) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field full">
    <label for="special_features">Special features</label>
    <input type="text" id="special_features" name="special_features" placeholder="e.g. Volume knob, screen" value="<?= $get('special_features') ?>">
  </div>
  <div class="field full">
    <label for="formerly_known_as_id">Formerly known as</label>
    <select id="formerly_known_as_id" name="formerly_known_as_id">
      <option value="">— none —</option>
      <?php foreach ($allKeyboards as $other):
          if (!empty($kb['id']) && $other['id'] == $kb['id']) continue; ?>
        <option value="<?= $other['id'] ?>" <?= (!empty($kb['formerly_known_as_id']) && $kb['formerly_known_as_id'] == $other['id']) ? 'selected' : '' ?>>
          <?= h($other['name']) ?><?= $other['model'] ? ' — ' . h($other['model']) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span class="hint">Link to a previous version of this build, if this is a rebuild.</span>
  </div>

  <div class="form-section-title">Write-up</div>
  <div class="field full" style="margin-top:-10px;">
    <span class="hint">You can drop photos into any of these: <code>![alt text](photo link)</code> for a plain
    photo, or <code>[![alt text](photo link)](click link)</code> to make it clickable.</span>
  </div>
  <div class="field full">
    <span class="hint">
      In any of these fields you can drop in a photo: <code>![alt text](photo url)</code>
      for a plain photo, or <code>[![alt text](photo url)](link url)</code> to make it
      clickable. Paste the URL of an image hosted anywhere (or one from this site's
      own gallery/upload).
    </span>
  </div>
  <div class="field full" style="margin-top:-10px;">
    <span class="hint">
      Link to another keyboard in any of the fields below: <code>[text](Keyboard Name)</code> or
      <code>[text](3)</code> for its ID, or <code>[text](https://...)</code> for an outside link.
    </span>
  </div>

  <div class="field full">
    <label for="intro_paragraph">Intro paragraph</label>
    <textarea id="intro_paragraph" name="intro_paragraph" rows="3"><?= $get('intro_paragraph') ?></textarea>
  </div>
  <div class="field full">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4"><?= $get('description') ?></textarea>
  </div>
  <div class="field full">
    <label for="build_timeline">Build timeline</label>
    <textarea id="build_timeline" name="build_timeline" rows="4"><?= $get('build_timeline') ?></textarea>
  </div>
  <div class="field full">
    <label for="personal_review">Personal review</label>
    <textarea id="personal_review" name="personal_review" rows="4"><?= $get('personal_review') ?></textarea>
  </div>
  <div class="field full">
    <label for="future_build_plans">Future build plans</label>
    <textarea id="future_build_plans" name="future_build_plans" rows="4"><?= $get('future_build_plans') ?></textarea>
  </div>

  <div class="field full form-actions">
    <a class="btn" href="<?= base_url(!empty($kb['id']) ? 'keyboard.php?id=' . $kb['id'] : 'index.php') ?>">Cancel</a>
    <button class="btn btn-accent" type="submit"><?= !empty($kb['id']) ? 'Save changes' : 'Add keyboard' ?></button>
  </div>
</form>
