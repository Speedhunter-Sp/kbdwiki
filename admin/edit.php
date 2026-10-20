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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Your session expired — please try submitting again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Please give the keyboard a name.';
    } elseif ((int)($_POST['formerly_known_as_id'] ?? 0) === $id) {
        $error = 'A keyboard can\'t be a former version of itself.';
    } else {
        $upload = handle_photo_upload('photo', $kb['photo']);
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
            $set = [];
            $params = [];
            foreach ($fields as $f) {
                $v = trim($_POST[$f] ?? '');
                $set[] = "$f = ?";
                $params[] = $v === '' ? null : $v;
            }
            $set[] = 'photo = ?';
            $params[] = $upload['filename'];

            $formerly = $_POST['formerly_known_as_id'] ?? '';
            $set[] = 'formerly_known_as_id = ?';
            $params[] = $formerly !== '' ? (int)$formerly : null;

            $params[] = $id;
            $stmt = get_db()->prepare('UPDATE keyboards SET ' . implode(', ', $set) . ' WHERE id = ?');
            $stmt->execute($params);

            sync_tags($id, trim($_POST['tags'] ?? ''));

            $deleteIds = array_map('intval', $_POST['delete_photo'] ?? []);
            delete_gallery_photos($id, $deleteIds);

            $gallery = handle_gallery_upload('gallery');
            add_gallery_photos($id, $gallery['filenames']);

            header('Location: ' . base_url('keyboard.php?id=' . $id));
            exit;
        }
    }
    }
    $kb = array_merge($kb, $_POST);
}

$allKeyboards = all_keyboards('name ASC');
$tagsCsv = $_POST['tags'] ?? tags_to_csv(tags_for_keyboard($id));
$galleryPhotos = gallery_for_keyboard($id);
$page_title = 'Edit ' . $kb['name'] . ' — ' . SITE_TITLE;
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Edit <?= h($kb['name']) ?></h1><p>Update specs, photo, or write-up.</p></div>
</div>
<div class="form-card">
  <?php require __DIR__ . '/../includes/kb-form.php'; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
