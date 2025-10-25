<?php
/** @var array $currentUser */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notes HQ</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header>
    <nav class="navbar">
        <div class="brand">
            <a href="index.php"><strong>Notes HQ</strong></a>
        </div>
        <div class="links">
            <a href="index.php">Dashboard</a>
            <a href="note_form.php">New Note</a>
            <?php if (user_has_role($currentUser, ['admin'])): ?>
                <a href="user_admin.php">Team</a>
            <?php endif; ?>
            <span>Signed in as <strong><?= h($currentUser['username']) ?></strong> (<?= h(ucfirst($currentUser['role'])) ?>)</span>
            <a href="logout.php">Sign out</a>
        </div>
    </nav>
</header>
<div class="container">
    <?php foreach (get_flashes() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="flash <?= h($type) ?>"><?= h($message) ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>
