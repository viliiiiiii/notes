<?php

declare(strict_types=1);

function find_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function all_users(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, username, role, email, created_at FROM users ORDER BY username');
    return $stmt->fetchAll();
}

function create_user(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
    $stmt->execute([
        'username' => $data['username'],
        'email' => $data['email'],
        'password_hash' => $data['password_hash'],
        'role' => $data['role'],
    ]);

    return (int) $pdo->lastInsertId();
}

function user_has_role(array $user, $roles): bool
{
    $roles = is_array($roles) ? $roles : [$roles];

    return in_array($user['role'], $roles, true);
}

function users_for_sharing(PDO $pdo, int $excludeUserId): array
{
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id != :id ORDER BY username');
    $stmt->execute(['id' => $excludeUserId]);

    return $stmt->fetchAll();
}
