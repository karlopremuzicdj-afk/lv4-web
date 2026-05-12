<?php
declare(strict_types=1);

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function selected(string $current, string $value): string
{
    return $current === $value ? ' selected' : '';
}

function checked(string $current, string $value): string
{
    return $current === $value ? ' checked' : '';
}

function safe_redirect_target(string $target, string $fallback = 'index.php'): string
{
    if ($target === '') {
        return $fallback;
    }

    if (preg_match('/^[a-z]+:/i', $target)) {
        return $fallback;
    }

    if (str_starts_with($target, '//')) {
        return $fallback;
    }

    return $target;
}

function current_relative_url(string $fallback = 'index.php'): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? $fallback;

    if (!is_string($uri) || $uri === '') {
        return $fallback;
    }

    return safe_redirect_target($uri, $fallback);
}

function format_role(string $role): string
{
    return $role === 'admin' ? 'Administrator' : 'Korisnik';
}

function value_from(array $source, string $key, string $default = ''): string
{
    $value = $source[$key] ?? $default;

    return is_string($value) ? $value : $default;
}
