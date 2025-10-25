<?php
require_once __DIR__ . '/bootstrap.php';

$currentUser = require_login($pdo);

$noteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$note = null;
$tags = [];
$isEdit = $noteId > 0;

if ($isEdit) {
    $note = find_note($pdo, $noteId);
    if (!$note || !user_can_access_note($pdo, $currentUser, $note) || $note['owner_id'] !== $currentUser['id']) {
        set_flash('error', 'You cannot edit this note.');
        redirect('index.php');
    }
    $tags = get_note_tags($pdo, $noteId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf($_POST['csrf_token'] ?? null);

    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $rawTags = trim($_POST['tags'] ?? '');
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
    $isArchived = isset($_POST['is_archived']) ? 1 : 0;

    $tagList = array_filter(
        array_unique(array_map(static fn ($tag) => strtolower(trim($tag)), explode(',', $rawTags))),
        fn ($tag) => $tag !== ''
    );

    if ($title === '' || $body === '') {
        set_flash('error', 'Title and body are required.');
        remember_old($_POST);
        redirect($isEdit ? 'note_form.php?id=' . $noteId : 'note_form.php');
    }

    if ($isEdit) {
        update_note($pdo, $noteId, [
            'title' => $title,
            'body' => $body,
            'is_pinned' => $isPinned,
            'is_archived' => $isArchived,
        ]);
        sync_note_tags($pdo, $noteId, $tagList);
        set_flash('success', 'Note updated.');
    } else {
        $noteId = create_note($pdo, [
            'owner_id' => (int) $currentUser['id'],
            'title' => $title,
            'body' => $body,
            'is_pinned' => $isPinned,
            'is_archived' => $isArchived,
        ]);
        sync_note_tags($pdo, $noteId, $tagList);
        set_flash('success', 'Note created.');
    }

    // Attachment handling example for MinIO (commented out for local testing):
    // require_once __DIR__ . '/storage/minio_client.php';
    // if (!empty($_FILES['attachment']['name'])) {
    //     $attachmentPath = upload_attachment_to_minio($_FILES['attachment'], $noteId);
    //     // Store $attachmentPath in a dedicated table if enabling attachments.
    // }

    clear_old();
    redirect('note.php?id=' . $noteId);
}

include __DIR__ . '/partials/header.php';
?>
<h1><?= $isEdit ? 'Edit note' : 'New note' ?></h1>
<form method="post" action="note_form.php<?= $isEdit ? '?id=' . $noteId : '' ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="field">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" value="<?= h($note['title'] ?? old('title')) ?>" required>
    </div>
    <div class="field">
        <label for="body">Body</label>
        <textarea name="body" id="body" rows="10" required><?= h($note['body'] ?? old('body')) ?></textarea>
    </div>
    <div class="field">
        <label for="tags">Tags</label>
        <input type="text" name="tags" id="tags" placeholder="comma,separated,tags" value="<?= h($note ? implode(',', $tags) : old('tags')) ?>">
    </div>
    <div class="field checkbox-group">
        <label><input type="checkbox" name="is_pinned" <?= (!empty($note['is_pinned']) || old('is_pinned')) ? 'checked' : '' ?>> Pin to dashboard</label>
        <label><input type="checkbox" name="is_archived" <?= (!empty($note['is_archived']) || old('is_archived')) ? 'checked' : '' ?>> Archive immediately</label>
    </div>
    <div class="field">
        <label for="attachment">Attachment (stored in MinIO)</label>
        <!-- Uncomment when MinIO bucket is configured -->
        <!-- <input type="file" name="attachment" id="attachment"> -->
        <p class="card-meta">Configure MinIO credentials in <code>config.php</code> and uncomment the upload section when ready.</p>
    </div>
    <button type="submit">Save note</button>
</form>
<?php include __DIR__ . '/partials/footer.php';
