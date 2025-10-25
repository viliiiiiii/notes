<?php
require_once __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($pdo, $username, $password)) {
        clear_old();
        redirect('index.php');
    } else {
        $error = 'Invalid credentials.';
        remember_old(['username' => $username]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign in - Notes HQ</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="container" style="max-width:480px;">
    <h1>Welcome back</h1>
    <p>Please sign in using one of the test accounts.</p>
    <?php if ($error): ?>
        <div class="flash error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <div class="field">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= h(old('username')) ?>" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <input type="submit" value="Sign in">
    </form>
    <div style="margin-top:1.5rem;">
        <h3>Test accounts</h3>
        <ul>
            <li><strong>admin</strong> / <code>Admin!234</code></li>
            <li><strong>manager</strong> / <code>Manager!234</code></li>
            <li><strong>editor</strong> / <code>Editor!234</code></li>
            <li><strong>viewer</strong> / <code>Viewer!234</code></li>
        </ul>
    </div>
</div>
</body>
</html>
