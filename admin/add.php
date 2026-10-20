<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$error = null;
$kb = ['connection_type' => '', 'hotswap' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Your session expired — please try submitting again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Please give the keyboard a name.';
    } else {
        $upload = handle_photo_upload('photo');
        if (!$upload['ok']) {
            $error = $upload['error'];
        } else {
            $fields = [
                'name', 'model', 'layout', 'frame_color', 'frame_swap', 'plate_type',
                'pcb_type', 'mounting_style', 'switches', 'keycaps', 'keycap_set',
                'connection_type', 'battery_size', 'hotswap', 'special_features',
                'intro_paragraph', 'description', 'build_timeline', 'personal_review',
                'future_build_plans',
            ];
            $values = [];
            foreach ($fields as $f) {
                $v = trim($_POST[$f] ?? '');
                $values[$f] = $v === '' ? null : $v;
            }
            $formerly = $_POST['formerly_known_as_id'] ?? '';
            $formerlyId = $formerly !== '' ? (int)$formerly : null;

            $cols = array_merge($fields, ['photo', 'formerly_known_as_id']);
            $placeholders = implode(', ', array_fill(0, count($cols), '?'));
            $stmt = get_db()->prepare('INSERT INTO keyboards (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')');
            $params = array_values($values);
            $params[] = $upload['filename'];
            $params[] = $formerlyId;
            $stmt->execute($params);
            $newId = (int)get_db()->lastInsertId();

            sync_tags($newId, trim($_POST['tags'] ?? ''));
            $gallery = handle_gallery_upload('gallery');
            add_gallery_photos($newId, $gallery['filenames']);

            header('Location: ' . base_url('keyboard.php?id=' . $newId));
            exit;
        }
    }
    }
    $kb = array_merge($kb, $_POST);
}

$allKeyboards = all_keyboards('name ASC');
$tagsCsv = $_POST['tags'] ?? '';
$page_title = 'Add a keyboard — ' . SITE_TITLE;
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Add a keyboard</h1><p>New build, new wiki page.</p></div>
</div>
<div class="form-card">
  <?php require __DIR__ . '/../includes/kb-form.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
