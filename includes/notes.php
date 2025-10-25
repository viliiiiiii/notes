<?php

declare(strict_types=1);

function find_note(PDO $pdo, int $noteId): ?array
{
    $stmt = $pdo->prepare('SELECT n.*, u.username AS owner_username FROM notes n JOIN users u ON n.owner_id = u.id WHERE n.id = :id LIMIT 1');
    $stmt->execute(['id' => $noteId]);
    $note = $stmt->fetch();

    return $note ?: null;
}

function fetch_notes_for_owner(PDO $pdo, int $ownerId, string $search = ''): array
{
    $sql = <<<SQL
        SELECT n.*, COUNT(DISTINCT ns.shared_with_user_id) AS shared_count
        FROM notes n
        LEFT JOIN note_shares ns ON ns.note_id = n.id
        LEFT JOIN note_tags nt ON nt.note_id = n.id
        WHERE n.owner_id = :owner_id
          AND (:search = '' OR n.title LIKE :like OR n.body LIKE :like OR nt.tag LIKE :like)
        GROUP BY n.id
        ORDER BY n.is_pinned DESC, n.updated_at DESC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'owner_id' => $ownerId,
        'search' => $search,
        'like' => '%' . $search . '%',
    ]);

    return $stmt->fetchAll();
}

function fetch_shared_notes(PDO $pdo, int $userId, string $search = ''): array
{
    $sql = <<<SQL
        SELECT n.*, ns.shared_at, u.username AS owner_username
        FROM note_shares ns
        JOIN notes n ON n.id = ns.note_id
        JOIN users u ON u.id = n.owner_id
        LEFT JOIN note_tags nt ON nt.note_id = n.id
        WHERE ns.shared_with_user_id = :user_id
          AND n.is_archived = 0
          AND (:search = '' OR n.title LIKE :like OR n.body LIKE :like OR nt.tag LIKE :like)
        GROUP BY n.id
        ORDER BY n.updated_at DESC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'search' => $search,
        'like' => '%' . $search . '%',
    ]);

    return $stmt->fetchAll();
}

function fetch_team_notes(PDO $pdo, int $excludeUserId, string $search = ''): array
{
    $sql = <<<SQL
        SELECT n.*, u.username AS owner_username, COUNT(DISTINCT ns.shared_with_user_id) AS shared_count
        FROM notes n
        JOIN users u ON u.id = n.owner_id
        LEFT JOIN note_shares ns ON ns.note_id = n.id
        LEFT JOIN note_tags nt ON nt.note_id = n.id
        WHERE n.owner_id != :exclude_user
          AND (:search = '' OR n.title LIKE :like OR n.body LIKE :like OR nt.tag LIKE :like)
        GROUP BY n.id
        ORDER BY n.updated_at DESC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'exclude_user' => $excludeUserId,
        'search' => $search,
        'like' => '%' . $search . '%',
    ]);

    return $stmt->fetchAll();
}

function create_note(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO notes (owner_id, title, body, is_pinned, is_archived) VALUES (:owner_id, :title, :body, :is_pinned, :is_archived)');
    $stmt->execute([
        'owner_id' => $data['owner_id'],
        'title' => $data['title'],
        'body' => $data['body'],
        'is_pinned' => $data['is_pinned'] ?? 0,
        'is_archived' => $data['is_archived'] ?? 0,
    ]);

    return (int) $pdo->lastInsertId();
}

function update_note(PDO $pdo, int $noteId, array $data): void
{
    $stmt = $pdo->prepare('UPDATE notes SET title = :title, body = :body, is_pinned = :is_pinned, is_archived = :is_archived, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'id' => $noteId,
        'title' => $data['title'],
        'body' => $data['body'],
        'is_pinned' => $data['is_pinned'] ?? 0,
        'is_archived' => $data['is_archived'] ?? 0,
    ]);
}

function delete_note(PDO $pdo, int $noteId): void
{
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM note_tags WHERE note_id = :id')->execute(['id' => $noteId]);
    $pdo->prepare('DELETE FROM note_replies WHERE note_id = :id')->execute(['id' => $noteId]);
    $pdo->prepare('DELETE FROM note_shares WHERE note_id = :id')->execute(['id' => $noteId]);
    $pdo->prepare('DELETE FROM notes WHERE id = :id')->execute(['id' => $noteId]);
    $pdo->commit();
}

function toggle_note_pin(PDO $pdo, int $noteId, bool $pin): void
{
    $stmt = $pdo->prepare('UPDATE notes SET is_pinned = :pin, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'id' => $noteId,
        'pin' => $pin ? 1 : 0,
    ]);
}

function toggle_note_archive(PDO $pdo, int $noteId, bool $archive): void
{
    $stmt = $pdo->prepare('UPDATE notes SET is_archived = :archived, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'id' => $noteId,
        'archived' => $archive ? 1 : 0,
    ]);
}

function sync_note_tags(PDO $pdo, int $noteId, array $tags): void
{
    $pdo->prepare('DELETE FROM note_tags WHERE note_id = :id')->execute(['id' => $noteId]);

    if (empty($tags)) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO note_tags (note_id, tag) VALUES (:note_id, :tag)');
    foreach ($tags as $tag) {
        $stmt->execute([
            'note_id' => $noteId,
            'tag' => $tag,
        ]);
    }
}

function get_note_tags(PDO $pdo, int $noteId): array
{
    $stmt = $pdo->prepare('SELECT tag FROM note_tags WHERE note_id = :id ORDER BY tag');
    $stmt->execute(['id' => $noteId]);

    return array_column($stmt->fetchAll(), 'tag');
}

function share_note_with_user(PDO $pdo, int $noteId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM note_shares WHERE note_id = :note_id AND shared_with_user_id = :user_id');
    $stmt->execute(['note_id' => $noteId, 'user_id' => $userId]);

    if ($stmt->fetch()) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO note_shares (note_id, shared_with_user_id) VALUES (:note_id, :user_id)');
    $stmt->execute([
        'note_id' => $noteId,
        'user_id' => $userId,
    ]);
}

function unshare_note_with_user(PDO $pdo, int $noteId, int $userId): void
{
    $stmt = $pdo->prepare('DELETE FROM note_shares WHERE note_id = :note_id AND shared_with_user_id = :user_id');
    $stmt->execute([
        'note_id' => $noteId,
        'user_id' => $userId,
    ]);
}

function get_note_share_recipients(PDO $pdo, int $noteId): array
{
    $stmt = $pdo->prepare('SELECT u.id, u.username, u.role, ns.shared_at FROM note_shares ns JOIN users u ON u.id = ns.shared_with_user_id WHERE ns.note_id = :note_id ORDER BY u.username');
    $stmt->execute(['note_id' => $noteId]);

    return $stmt->fetchAll();
}

function note_is_shared_with(PDO $pdo, int $noteId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM note_shares WHERE note_id = :note_id AND shared_with_user_id = :user_id LIMIT 1');
    $stmt->execute([
        'note_id' => $noteId,
        'user_id' => $userId,
    ]);

    return (bool) $stmt->fetchColumn();
}

function user_can_access_note(PDO $pdo, array $user, array $note): bool
{
    if ($note['owner_id'] === $user['id'] || user_has_role($user, 'admin')) {
        return true;
    }

    if (note_is_shared_with($pdo, (int) $note['id'], (int) $user['id'])) {
        return true;
    }

    if (user_has_role($user, 'manager')) {
        return true;
    }

    return false;
}

function add_note_reply(PDO $pdo, int $noteId, int $userId, string $content): void
{
    $stmt = $pdo->prepare('INSERT INTO note_replies (note_id, user_id, content) VALUES (:note_id, :user_id, :content)');
    $stmt->execute([
        'note_id' => $noteId,
        'user_id' => $userId,
        'content' => $content,
    ]);
}

function fetch_note_replies(PDO $pdo, int $noteId): array
{
    $stmt = $pdo->prepare('SELECT nr.*, u.username FROM note_replies nr JOIN users u ON u.id = nr.user_id WHERE nr.note_id = :note_id ORDER BY nr.created_at');
    $stmt->execute(['note_id' => $noteId]);

    return $stmt->fetchAll();
}
