<?php
require_once __DIR__ . '/db.php';

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Only allow http(s) links (or scheme-less/relative paths) through as a URL —
// blocks things like javascript: from a pasted link.
function safe_url(string $url): string {
    $trimmed = trim($url);
    if (preg_match('#^([a-z][a-z0-9+.\-]*):#i', $trimmed, $m)) {
        if (!in_array(strtolower($m[1]), ['http', 'https'], true)) {
            return '#';
        }
    }
    return $trimmed;
}

// A tiny bit of markdown, just for photos, used in the long write-up fields:
//   ![alt text](photo url)                 -> a plain photo
//   [![alt text](photo url)](link url)     -> a photo that links out when clicked
// Everything else is escaped first, so no other HTML can sneak in.
function render_body_text(?string $text): string {
    if ($text === null || $text === '') return '';
    $escaped = h($text);

    // clickable photo: [ (optional !) [alt](photoUrl) ](linkUrl)
    $escaped = preg_replace_callback(
        '/\[!?\[([^\]]*)\]\(([^)]+)\)\]\(([^)]+)\)/',
        function ($m) {
            $alt = $m[1];
            $img = safe_url($m[2]);
            $link = safe_url($m[3]);
            return '<a href="' . $link . '" class="body-photo-link" target="_blank" rel="noopener">'
                 . '<img src="' . $img . '" alt="' . $alt . '" class="body-photo" loading="lazy"></a>';
        },
        $escaped
    );

    // plain photo: ![alt](photoUrl)
    $escaped = preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)]+)\)/',
        function ($m) {
            $alt = $m[1];
            $img = safe_url($m[2]);
            return '<img src="' . $img . '" alt="' . $alt . '" class="body-photo" loading="lazy">';
        },
        $escaped
    );

    // plain text link: [text](target) — target can be another keyboard's
    // name, its numeric ID, or an outside https:// URL. Anything the earlier
    // photo passes already handled is gone by now, so any [..](..) left at
    // this point is a genuine link. Left as literal text if it can't be resolved.
    $escaped = preg_replace_callback(
        '/\[([^\]]*)\]\(([^)]+)\)/',
        function ($m) {
            $label = $m[1];
            $resolved = resolve_link_target($m[2]);
            if ($resolved === null) {
                // couldn't resolve it — show it plainly instead of raw markdown text
                return '<span class="broken-link" title="Link target not found: ' . $m[2] . '">' . $label . '</span>';
            }
            $external = preg_match('#^https?://#i', trim($m[2]));
            $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $resolved . '" class="body-link"' . $attrs . '>' . $label . '</a>';
        },
        $escaped
    );

    return nl2br($escaped);
}

// Resolves a [text](target) link's target to an actual URL:
//   a plain number       -> that keyboard's ID
//   matches an existing keyboard's name (case-insensitive) -> that keyboard
//   an http(s):// URL    -> the URL itself (scheme-checked via safe_url)
// Returns null if none of those match, so the caller can leave the text as-is.
function resolve_link_target(string $target): ?string {
    $target = trim($target);
    if ($target === '') return null;

    if (preg_match('#^https?://#i', $target)) {
        return safe_url($target);
    }

    if (ctype_digit($target)) {
        $kb = find_keyboard((int)$target);
        return $kb ? base_url('keyboard.php?id=' . $kb['id']) : null;
    }

    $stmt = get_db()->prepare('SELECT id FROM keyboards WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$target]);
    $row = $stmt->fetch();
    return $row ? base_url('keyboard.php?id=' . $row['id']) : null;
}

// The canonical list of spec-grid fields: [db_column, label].
// Used by the detail page, the add/edit form, and the comparison table,
// so every place the specs are shown stays in sync.
function spec_fields(): array {
    return [
        ['model',            'Model'],
        ['layout',           'Layout'],
        ['frame_color',      'Frame color'],
        ['frame_swap',       'Frame swap'],
        ['plate_type',       'Plate type'],
        ['pcb_type',         'PCB type'],
        ['mounting_style',   'Mounting style'],
        ['switches',         'Switches'],
        ['keycaps',          'Keycaps'],
        ['keycap_set',       'Keycap set'],
        ['connection_type',  'Connection type'],
        ['battery_size',     'Battery size'],
        ['hotswap',          'Hotswap'],
        ['special_features', 'Special features'],
    ];
}

function find_keyboard(int $id): ?array {
    $stmt = get_db()->prepare('SELECT * FROM keyboards WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function all_keyboards(string $order = 'name ASC'): array {
    $allowed = ['name ASC', 'created_at DESC', 'updated_at DESC'];
    if (!in_array($order, $allowed, true)) $order = 'name ASC';
    return get_db()->query("SELECT * FROM keyboards ORDER BY $order")->fetchAll();
}

// Homepage search: free-text over name/model, optionally narrowed to one tag.
function search_keyboards(string $q = '', string $tagName = ''): array {
    $db = get_db();
    $sql = 'SELECT DISTINCT k.* FROM keyboards k';
    $params = [];

    if ($tagName !== '') {
        $sql .= ' JOIN keyboard_tags kt ON kt.keyboard_id = k.id
                  JOIN tags t ON t.id = kt.tag_id AND LOWER(t.name) = LOWER(?)';
        $params[] = $tagName;
    }
    if ($q !== '') {
        $sql .= (str_contains($sql, 'WHERE') ? ' AND ' : ' WHERE ') . '(k.name LIKE ? OR k.model LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    $sql .= ' ORDER BY k.name ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ---------- tags ----------

function all_tags(): array {
    return get_db()->query(
        'SELECT t.*, COUNT(kt.keyboard_id) AS cnt
         FROM tags t LEFT JOIN keyboard_tags kt ON kt.tag_id = t.id
         GROUP BY t.id ORDER BY t.name ASC'
    )->fetchAll();
}

function tags_for_keyboard(int $keyboardId): array {
    $stmt = get_db()->prepare(
        'SELECT t.* FROM tags t
         JOIN keyboard_tags kt ON kt.tag_id = t.id
         WHERE kt.keyboard_id = ? ORDER BY t.name ASC'
    );
    $stmt->execute([$keyboardId]);
    return $stmt->fetchAll();
}

// Replaces a keyboard's tags with the comma-separated list in $tagsCsv,
// creating any tag names that don't exist yet.
function sync_tags(int $keyboardId, string $tagsCsv): void {
    $db = get_db();
    $names = array_unique(array_filter(array_map('trim', explode(',', $tagsCsv))));

    $tagIds = [];
    foreach ($names as $name) {
        if ($name === '') continue;
        $stmt = $db->prepare('SELECT id FROM tags WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        if ($row) {
            $tagIds[] = (int)$row['id'];
        } else {
            $ins = $db->prepare('INSERT INTO tags (name) VALUES (?)');
            $ins->execute([$name]);
            $tagIds[] = (int)$db->lastInsertId();
        }
    }

    $db->prepare('DELETE FROM keyboard_tags WHERE keyboard_id = ?')->execute([$keyboardId]);
    if ($tagIds) {
        $stmt = $db->prepare('INSERT IGNORE INTO keyboard_tags (keyboard_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tid) {
            $stmt->execute([$keyboardId, $tid]);
        }
    }
}

function tags_to_csv(array $tags): string {
    return implode(', ', array_map(fn($t) => $t['name'], $tags));
}

// ---------- gallery ----------

function gallery_for_keyboard(int $keyboardId): array {
    $stmt = get_db()->prepare('SELECT * FROM keyboard_photos WHERE keyboard_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$keyboardId]);
    return $stmt->fetchAll();
}

// Handles a multi-file <input type="file" name="gallery[]" multiple>.
// Returns ['filenames' => [...saved filenames...], 'errors' => [...]].
function handle_gallery_upload(string $inputName): array {
    $result = ['filenames' => [], 'errors' => []];
    if (empty($_FILES[$inputName]) || empty($_FILES[$inputName]['name'][0])) {
        return $result;
    }
    $files = $_FILES[$inputName];
    $allowed = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'webp' => 1, 'gif' => 1];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $result['errors'][] = $files['name'][$i] . ': upload failed.';
            continue;
        }
        if ($files['size'][$i] > MAX_UPLOAD_BYTES) {
            $result['errors'][] = $files['name'][$i] . ': larger than 8 MB.';
            continue;
        }
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            $result['errors'][] = $files['name'][$i] . ': unsupported file type.';
            continue;
        }
        if (@getimagesize($files['tmp_name'][$i]) === false) {
            $result['errors'][] = $files['name'][$i] . ': doesn\'t look like a valid image.';
            continue;
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_DIR . $filename)) {
            $result['filenames'][] = $filename;
        } else {
            $result['errors'][] = $files['name'][$i] . ': could not save.';
        }
    }
    return $result;
}

function add_gallery_photos(int $keyboardId, array $filenames): void {
    if (!$filenames) return;
    $db = get_db();
    $next = (int)$db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM keyboard_photos WHERE keyboard_id = ' . $keyboardId)->fetch()['n'];
    $stmt = $db->prepare('INSERT INTO keyboard_photos (keyboard_id, filename, sort_order) VALUES (?, ?, ?)');
    foreach ($filenames as $f) {
        $stmt->execute([$keyboardId, $f, $next++]);
    }
}

// Deletes the given keyboard_photos rows (only ones belonging to $keyboardId)
// and their files on disk.
function delete_gallery_photos(int $keyboardId, array $photoIds): void {
    if (!$photoIds) return;
    $db = get_db();
    $in = implode(',', array_fill(0, count($photoIds), '?'));
    $stmt = $db->prepare("SELECT * FROM keyboard_photos WHERE keyboard_id = ? AND id IN ($in)");
    $stmt->execute(array_merge([$keyboardId], $photoIds));
    foreach ($stmt->fetchAll() as $row) {
        if ($row['filename'] && file_exists(UPLOAD_DIR . $row['filename'])) {
            @unlink(UPLOAD_DIR . $row['filename']);
        }
    }
    $del = $db->prepare("DELETE FROM keyboard_photos WHERE keyboard_id = ? AND id IN ($in)");
    $del->execute(array_merge([$keyboardId], $photoIds));
}

function photo_src(?string $photo): string {
    if ($photo && file_exists(UPLOAD_DIR . $photo)) {
        return base_url(UPLOAD_URL . rawurlencode($photo));
    }
    // simple inline placeholder (no external request needed)
    return '';
}

// Handles a single uploaded photo. Returns the stored filename, the
// existing filename (unchanged), or null if there is a hard error.
function handle_photo_upload(string $inputName, ?string $existing = null): array {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => $existing];
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed (error code ' . $file['error'] . ').'];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'That photo is larger than 8 MB.'];
    }
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'error' => 'Please upload a JPG, PNG, WEBP, or GIF image.'];
    }
    // Belt-and-braces: confirm the file is actually an image, not just named like one.
    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'That file doesn\'t look like a valid image.'];
    }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded photo.'];
    }
    return ['ok' => true, 'filename' => $filename];
}