<?php
require_once __DIR__ . '/bootstrap.php';

$currentUser = require_login($pdo);
require_role($currentUser, ['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf($_POST['csrf_token'] ?? null);

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'viewer';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        set_flash('error', 'All fields are required.');
        redirect('user_admin.php');
    }

    if (find_user_by_username($pdo, $username)) {
        set_flash('error', 'Username already exists.');
        redirect('user_admin.php');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    create_user($pdo, [
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);

    set_flash('success', 'User created.');
    redirect('user_admin.php');
}

$users = all_users($pdo);

include __DIR__ . '/partials/header.php';
?>
<h1>Team management</h1>
<div class="card">
    <h2>Create teammate</h2>
    <form method="post" action="user_admin.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="field">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="field">
            <label for="role">Role</label>
            <select name="role" id="role">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
            </select>
        </div>
        <div class="field">
            <label for="password">Temporary password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit">Create user</button>
    </form>
</div>

<div class="card">
    <h2>Existing members</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= h($user['username']) ?></td>
                <td><?= h($user['email']) ?></td>
                <td><?= h(ucfirst($user['role'])) ?></td>
                <td><?= h(format_datetime($user['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/footer.php';
