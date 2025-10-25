<?php
require_once __DIR__ . '/bootstrap.php';

$currentUser = require_login($pdo);

$noteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$note = $noteId ? find_note($pdo, $noteId) : null;

if (!$note || $note['owner_id'] !== $currentUser['id']) {
    set_flash('error', 'You can only share notes you own.');
    redirect('index.php');
}

if (isset($_GET['unshare'])) {
    require_csrf($_GET['token'] ?? null);
    $userId = (int) $_GET['unshare'];
    unshare_note_with_user($pdo, $noteId, $userId);
    set_flash('success', 'Access revoked.');
    redirect('share_note.php?id=' . $noteId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf($_POST['csrf_token'] ?? null);
    $userToShare = (int) ($_POST['user_id'] ?? 0);

    if ($userToShare <= 0) {
        set_flash('error', 'Select a teammate to share with.');
        redirect('share_note.php?id=' . $noteId);
    }

    if ($userToShare === (int) $currentUser['id']) {
        set_flash('error', 'You already own this note.');
        redirect('share_note.php?id=' . $noteId);
    }

    share_note_with_user($pdo, $noteId, $userToShare);
    set_flash('success', 'Note shared successfully.');
    redirect('share_note.php?id=' . $noteId);
}

$users = users_for_sharing($pdo, (int) $currentUser['id']);
$recipients = get_note_share_recipients($pdo, $noteId);

include __DIR__ . '/partials/header.php';
?>
<h1>Share "<?= h($note['title']) ?>"</h1>
<div class="card">
    <form method="post" action="share_note.php?id=<?= $noteId ?>">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="field">
            <label for="user_id">Share with</label>
            <select name="user_id" id="user_id" required>
                <option value="">Select a user</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>"><?= h($user['username']) ?> (<?= h($user['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Share note</button>
    </form>
</div>

<div class="card">
    <h3>Current collaborators</h3>
    <?php if ($recipients): ?>
        <ul>
            <?php foreach ($recipients as $recipient): ?>
                <li>
                    <?= h($recipient['username']) ?> (<?= h($recipient['role']) ?>)
                    <a href="share_note.php?id=<?= $noteId ?>&unshare=<?= (int) $recipient['id'] ?>&token=<?= h(csrf_token()) ?>" style="margin-left:0.5rem;">Remove</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No collaborators yet.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php';
