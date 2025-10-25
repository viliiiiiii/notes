<?php
require_once __DIR__ . '/bootstrap.php';

$currentUser = require_login($pdo);

$search = trim($_GET['q'] ?? '');
$includeArchived = isset($_GET['show_archived']);

$ownNotes = fetch_notes_for_owner($pdo, (int) $currentUser['id'], $search);
$sharedNotes = fetch_shared_notes($pdo, (int) $currentUser['id'], $search);
$teamNotes = [];

if (user_has_role($currentUser, ['admin', 'manager'])) {
    $teamNotes = fetch_team_notes($pdo, (int) $currentUser['id'], $search);
}

$pinned = array_filter($ownNotes, fn ($note) => !$note['is_archived'] && $note['is_pinned']);
$activeNotes = array_filter($ownNotes, fn ($note) => !$note['is_archived'] && !$note['is_pinned']);
$archivedNotes = array_filter($ownNotes, fn ($note) => $note['is_archived']);

include __DIR__ . '/partials/header.php';
?>
<h1>Dashboard</h1>
<form method="get" action="index.php" class="search-bar">
    <input type="text" name="q" placeholder="Search by title, content, or tag" value="<?= h($search) ?>">
    <label class="checkbox-group">
        <input type="checkbox" name="show_archived" value="1" <?= $includeArchived ? 'checked' : '' ?>>
        Show archived
    </label>
    <button type="submit">Search</button>
</form>

<?php if ($pinned): ?>
    <h2 class="section-title">Pinned notes</h2>
    <?php foreach ($pinned as $note): ?>
        <?php include __DIR__ . '/partials/note_card.php'; ?>
    <?php endforeach; ?>
<?php endif; ?>

<h2 class="section-title">My notes</h2>
<?php if ($activeNotes): ?>
    <?php foreach ($activeNotes as $note): ?>
        <?php include __DIR__ . '/partials/note_card.php'; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p>No notes yet. <a href="note_form.php">Create one</a>.</p>
<?php endif; ?>

<h2 class="section-title">Shared with me</h2>
<?php if ($sharedNotes): ?>
    <?php foreach ($sharedNotes as $note): ?>
        <?php $isSharedWithMe = true; ?>
        <?php include __DIR__ . '/partials/note_card.php'; ?>
        <?php $isSharedWithMe = false; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p>No notes shared with you yet.</p>
<?php endif; ?>

<?php if ($includeArchived): ?>
    <h2 class="section-title">Archived</h2>
    <?php if ($archivedNotes): ?>
        <?php foreach ($archivedNotes as $note): ?>
            <?php include __DIR__ . '/partials/note_card.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No archived notes.</p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($teamNotes): ?>
    <h2 class="section-title">Team visibility</h2>
    <p class="card-meta">As an <?= h($currentUser['role']) ?> you can review team notes for coaching and compliance.</p>
    <?php foreach ($teamNotes as $note): ?>
        <?php $isTeamNote = true; ?>
        <?php include __DIR__ . '/partials/note_card.php'; ?>
        <?php $isTeamNote = false; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php';
