<?php
require_once __DIR__ . '/../bootstrap.php';

$currentUser = require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

require_csrf($_POST['csrf_token'] ?? null);

$noteId = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
$action = $_POST['action'] ?? '';
$note = $noteId ? find_note($pdo, $noteId) : null;

if (!$note) {
    set_flash('error', 'Note not found.');
    redirect('../index.php');
}

$isOwner = $note['owner_id'] === $currentUser['id'];
$canManage = $isOwner || user_has_role($currentUser, ['admin']);

if (!$canManage) {
    set_flash('error', 'You cannot manage this note.');
    redirect('../index.php');
}

switch ($action) {
    case 'pin':
        toggle_note_pin($pdo, $noteId, true);
        set_flash('success', 'Note pinned.');
        break;
    case 'unpin':
        toggle_note_pin($pdo, $noteId, false);
        set_flash('success', 'Note unpinned.');
        break;
    case 'archive':
        toggle_note_archive($pdo, $noteId, true);
        set_flash('success', 'Note archived.');
        break;
    case 'unarchive':
        toggle_note_archive($pdo, $noteId, false);
        set_flash('success', 'Note restored.');
        break;
    case 'delete':
        delete_note($pdo, $noteId);
        set_flash('success', 'Note deleted.');
        break;
    default:
        set_flash('error', 'Unknown action.');
}

redirect('../index.php');
