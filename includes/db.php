<?php
require_once __DIR__ . '/../config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die(
                '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:24px;'
                . 'border:1px solid #c0533d;border-radius:8px;background:#fff3ef;color:#5a2a1e;">'
                . '<h2 style="margin-top:0;">Can&rsquo;t connect to the database</h2>'
                . '<p>Make sure MySQL is running in XAMPP and that you have imported <code>schema.sql</code>, '
                . 'then check the credentials in <code>config.php</code>.</p>'
                . '<p style="font-size:13px;opacity:.75;">' . htmlspecialchars($e->getMessage()) . '</p>'
                . '</div>'
            );
        }
    }
    return $pdo;
}
