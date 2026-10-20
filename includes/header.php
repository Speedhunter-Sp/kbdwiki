<?php
require_once __DIR__ . '/auth.php';
// Expects an optional $page_title to be set before include.
$page_title = $page_title ?? SITE_TITLE;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="shell">
    <a class="wordmark" href="<?= base_url('index.php') ?>">
      Keeb<span class="dot">&#9679;</span>Wiki
      <span class="tag">/personal build archive</span>
    </a>
    <nav class="nav-links">
      <a href="<?= base_url('index.php') ?>">Collection</a>
      <a href="<?= base_url('compare.php') ?>">Compare</a>
      <a href="<?= base_url('typing-test.php') ?>">Typing Test</a>
      <?php if (is_logged_in()): ?>
        <a href="<?= base_url('admin/add.php') ?>" class="btn btn-accent btn-sm">+ Add keyboard</a>
        <a href="<?= base_url('logout.php') ?>">Log out</a>
      <?php else: ?>
        <a href="<?= base_url('login.php') ?>">Admin login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="shell">
