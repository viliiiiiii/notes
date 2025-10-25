<?php

declare(strict_types=1);

require_once __DIR__ . '/users.php';

function init_session(array $config): void
{
    if (!empty($config['session_name'])) {
        session_name($config['session_name']);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login(PDO $pdo, string $username, string $password): bool
{
    $user = find_user_by_username($pdo, $username);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }

    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(PDO $pdo): array
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }

    $user = find_user_by_id($pdo, (int) $_SESSION['user_id']);

    if (!$user) {
        logout();
        redirect('login.php');
    }

    return $user;
}

function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return find_user_by_id($pdo, (int) $_SESSION['user_id']);
}

function require_role(array $user, $roles): void
{
    if (!user_has_role($user, $roles)) {
        http_response_code(403);
        exit('You are not authorized to perform this action.');
    }
}
