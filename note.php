<?php
require_once __DIR__ . '/bootstrap.php';

$currentUser = require_login($pdo);

$noteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$note = $noteId ? find_note($pdo, $noteId) : null;

if (!$note || !user_can_access_note($pdo, $currentUser, $note)) {
    set_flash('error', 'Note not found or you do not have access.');
    redirect('index.php');
}

$tags = get_note_tags($pdo, $noteId);
$recipients = get_note_share_recipients($pdo, $noteId);
$replies = fetch_note_replies($pdo, $noteId);
$isOwner = $note['owner_id'] === $currentUser['id'];
$canReply = $isOwner || note_is_shared_with($pdo, $noteId, (int) $currentUser['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    require_csrf($_POST['csrf_token'] ?? null);
    if (!$canReply) {
        set_flash('error', 'You cannot reply to this note.');
        redirect('note.php?id=' . $noteId);
    }
    $replyContent = trim($_POST['reply'] ?? '');
    if ($replyContent === '') {
        set_flash('error', 'Reply cannot be empty.');
        redirect('note.php?id=' . $noteId);
    }
    add_note_reply($pdo, $noteId, (int) $currentUser['id'], $replyContent);
    set_flash('success', 'Reply posted.');
    redirect('note.php?id=' . $noteId);
}

include __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-header">
        <div>
            <h1><?= h($note['title']) ?></h1>
            <div class="card-meta">
                Created <?= h(format_datetime($note['created_at'])) ?> · Last updated <?= h(format_datetime($note['updated_at'])) ?>
                <?php if (!$isOwner): ?>
                    · Owner <?= h($note['owner_username']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($recipients): ?>
                <span class="badge shared">Shared</span>
            <?php endif; ?>
            <?php if ($note['is_pinned']): ?>
                <span class="badge">Pinned</span>
            <?php endif; ?>
            <?php if ($note['is_archived']): ?>
                <span class="badge archived">Archived</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="note-content">
        <?= nl2br(h($note['body'])) ?>
    </div>
    <?php if ($tags): ?>
        <div class="tags">
            <?php foreach ($tags as $tag): ?>
                <span class="tag">#<?= h($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($isOwner): ?>
        <div class="actions">
            <a class="secondary" href="note_form.php?id=<?= $noteId ?>">Edit</a>
            <a class="secondary" href="share_note.php?id=<?= $noteId ?>">Share</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($recipients): ?>
    <div class="card">
        <h3>Shared with</h3>
        <ul>
            <?php foreach ($recipients as $recipient): ?>
                <li><?= h($recipient['username']) ?> (<?= h($recipient['role']) ?>) · since <?= h(format_datetime($recipient['shared_at'])) ?><?php if ($isOwner): ?> · <a href="share_note.php?id=<?= $noteId ?>&unshare=<?= (int) $recipient['id'] ?>">Remove access</a><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Activity</h3>
    <?php if ($replies): ?>
        <div class="reply-list">
            <?php foreach ($replies as $reply): ?>
                <div class="reply">
                    <div class="meta">Reply by <?= h($reply['username']) ?> · <?= h(format_datetime($reply['created_at'])) ?></div>
                    <div><?= nl2br(h($reply['content'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No replies yet.</p>
    <?php endif; ?>
    <?php if ($canReply): ?>
        <form method="post" action="note.php?id=<?= $noteId ?>" style="margin-top:1rem;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <textarea name="reply" rows="4" placeholder="Write a reply" required></textarea>
            <div style="margin-top:0.5rem;"><button type="submit">Post reply</button></div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php';
