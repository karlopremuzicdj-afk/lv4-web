<?php
declare(strict_types=1);

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function is_standard_user(): bool
{
    return is_logged_in() && !is_admin();
}

function log_user_in(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function log_user_out(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Za pristup ovoj stranici morate se prijaviti.');
        redirect('login.php?next=' . urlencode(current_relative_url()));
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        set_flash('error', 'Administratorske opcije dostupne su samo administratorima.');
        redirect('index.php');
    }
}

function require_standard_user(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Za pristup ovoj stranici morate se prijaviti.');
        redirect('login.php?next=' . urlencode(current_relative_url()));
    }

    if (is_admin()) {
        set_flash('info', 'Administratorski račun služi za upravljanje sadržajem, bez osobnih ocjena i videoteke.');
        redirect('index.php');
    }
}

function find_user_by_identity(PDO $pdo, string $identity): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, username, email, password_hash, role
         FROM users
         WHERE username = :username_identity OR email = :email_identity
         LIMIT 1',
    );
    $statement->execute([
        'username_identity' => $identity,
        'email_identity' => $identity,
    ]);
    $user = $statement->fetch();

    return $user ?: null;
}
