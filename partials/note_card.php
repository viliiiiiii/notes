<?php
/** @var array $note */
/** @var array $currentUser */
$isOwner = $note['owner_id'] === $currentUser['id'];
$isSharedWithMe = $isSharedWithMe ?? false;
$isTeamNote = $isTeamNote ?? false;
$tags = get_note_tags($pdo, (int) $note['id']);
$sharedRecipients = get_note_share_recipients($pdo, (int) $note['id']);
$isShared = !empty($sharedRecipients) || $isSharedWithMe;
$isArchived = (bool) $note['is_archived'];
$isPinned = (bool) $note['is_pinned'];
$canManage = $isOwner || user_has_role($currentUser, ['admin']);
?>
<div class="card">
    <div class="card-header">
        <div>
            <h3><a href="note.php?id=<?= (int) $note['id'] ?>"><?= h($note['title']) ?></a></h3>
            <div class="card-meta">
                <?php if (!$isOwner && isset($note['owner_username'])): ?>
                    Owner: <?= h($note['owner_username']) ?>
                <?php else: ?>
                    Updated <?= h(format_datetime($note['updated_at'])) ?>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($isPinned): ?>
                <span class="badge">Pinned</span>
            <?php endif; ?>
            <?php if ($isShared): ?>
                <span class="badge shared">Shared</span>
            <?php endif; ?>
            <?php if ($isSharedWithMe): ?>
                <span class="badge replyable">Can reply</span>
            <?php endif; ?>
            <?php if ($isArchived): ?>
                <span class="badge archived">Archived</span>
            <?php endif; ?>
            <?php if ($isTeamNote && isset($note['owner_username'])): ?>
                <span class="badge role">Team note</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="note-content">
        <?= nl2br(h(mb_strimwidth($note['body'], 0, 400, strlen($note['body']) > 400 ? '…' : ''))) ?>
    </div>
    <?php if ($tags): ?>
        <div class="tags">
            <?php foreach ($tags as $tag): ?>
                <span class="tag">#<?= h($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="actions">
        <a class="secondary" href="note.php?id=<?= (int) $note['id'] ?>">Open</a>
        <?php if ($canManage): ?>
            <form method="post" action="actions/note_action.php" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="note_id" value="<?= (int) $note['id'] ?>">
                <input type="hidden" name="action" value="<?= $isPinned ? 'unpin' : 'pin' ?>">
                <input type="submit" class="secondary" value="<?= $isPinned ? 'Unpin' : 'Pin' ?>">
            </form>
            <form method="post" action="actions/note_action.php" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="note_id" value="<?= (int) $note['id'] ?>">
                <input type="hidden" name="action" value="<?= $isArchived ? 'unarchive' : 'archive' ?>">
                <input type="submit" class="secondary" value="<?= $isArchived ? 'Restore' : 'Archive' ?>">
            </form>
            <a class="secondary" href="note_form.php?id=<?= (int) $note['id'] ?>">Edit</a>
            <form method="post" action="actions/note_action.php" onsubmit="return confirm('Delete this note?');" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="note_id" value="<?= (int) $note['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <input type="submit" class="danger" value="Delete">
            </form>
        <?php elseif ($isSharedWithMe && isset($note['shared_at'])): ?>
            <span class="card-meta">Shared by <?= h($note['owner_username']) ?> on <?= h(format_datetime($note['shared_at'])) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!empty($sharedRecipients) && $isOwner): ?>
        <div class="card-meta" style="margin-top:0.75rem;">
            Shared with:
            <?php foreach ($sharedRecipients as $recipient): ?>
                <span class="badge"><?= h($recipient['username']) ?> (<?= h($recipient['role']) ?>)</span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
