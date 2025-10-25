<?php

declare(strict_types=1);

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_datetime(string $datetime): string
{
    $dt = new DateTimeImmutable($datetime);
    return $dt->format('Y-m-d H:i');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf(?string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(?string $token): void
{
    if (!validate_csrf($token)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flashes'][$type][] = $message;
}

function get_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

function old(string $key, $default = '')
{
    if (!empty($_SESSION['old'][$key])) {
        return $_SESSION['old'][$key];
    }

    return $default;
}

function remember_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}
