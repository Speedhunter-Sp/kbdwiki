<?php
require_once __DIR__ . '/includes/auth.php';

// This page only works once. As soon as one user exists, it locks itself.
if (admin_account_exists()) {
    $locked = true;
} else {
    $locked = false;
}

$error = '';
if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password should be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords don\'t match.';
    } else {
        $stmt = get_db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: ' . base_url('login.php?created=1'));
        exit;
    }
}

$page_title = 'Set up Keeb Wiki';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="form-card">
    <?php if ($locked): ?>
      <h1>Already set up</h1>
      <p class="lead">An admin account already exists for this Keeb Wiki install.</p>
      <a class="btn btn-accent" href="<?= base_url('login.php') ?>">Go to login</a>
    <?php else: ?>
      <h1>Create your admin account</h1>
      <p class="lead">This runs once. You'll use this to log in and edit your collection.</p>
      <?php if ($error): ?><div class="flash"><?= h($error) ?></div><?php endif; ?>
      <form method="post" class="form-grid">
        <div class="field full">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="field full">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="8">
        </div>
        <div class="field full">
          <label for="confirm">Confirm password</label>
          <input type="password" id="confirm" name="confirm" required minlength="8">
        </div>
        <div class="field full">
          <button class="btn btn-accent" type="submit" style="width:100%;justify-content:center;">Create account</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
