<?php
// ---------------------------------------------------------------
// Keeb Wiki configuration
// Edit these if your XAMPP MySQL setup differs from the defaults.
// ---------------------------------------------------------------

define('DB_HOST', 'localhost');
define('DB_NAME', 'u793073111_keeb_wiki');
define('DB_USER', 'u793073111_Speedhunter');
define('DB_PASS', 'Sh_Sp54321');          // default XAMPP MySQL password is blank

define('SITE_TITLE', 'Keeb Wiki');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_UPLOAD_BYTES', 8 * 1024 * 1024); // 8 MB

// ---------------------------------------------------------------
// Set this to 'production' once you deploy somewhere public
// (Hostinger, etc). It hides PHP error details from visitors and
// tightens the session cookie. Keep it 'development' on XAMPP so
// you can actually see error messages while building.
// ---------------------------------------------------------------
define('APP_ENV', 'development');

if (APP_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// If you host this publicly and only want YOU to be able to log in,
// leave registration disabled (there is no public sign-up page —
// the single admin account is created once via setup.php).
