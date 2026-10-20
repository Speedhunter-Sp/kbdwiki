<?php
require_once __DIR__ . '/includes/auth.php';

if (!admin_account_exists()) {
    header('Location: ' . base_url('setup.php'));
    exit;
}

if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = get_db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ' . base_url('index.php'));
        exit;
    }
    $error = 'Incorrect username or password.';
}

$page_title = 'Log in — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="form-card">
    <h1>Admin login</h1>
    <p class="lead">Only you can edit the collection. Everyone else just browses.</p>
    <?php if ($error): ?><div class="flash"><?= h($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['created'])): ?><div class="flash ok">Account created — log in below.</div><?php endif; ?>
    <form method="post" class="form-grid">
      <div class="field full">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="field full">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div class="field full">
        <button class="btn btn-accent" type="submit" style="width:100%;justify-content:center;">Log in</button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
