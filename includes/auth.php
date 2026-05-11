<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    // The authenticated user is stored in the session after a successful login.
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(string $redirect = '../login.php'): void
{
    // Stop protected pages from rendering when the visitor is not signed in.
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(string $redirect = '../index.php'): void
{
    // Admin pages require both an active session and the admin role.
    require_login('../login.php');

    if (!is_admin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function csrf_token(): string
{
    // Reuse one token per session so forms can validate POST requests safely.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    // hash_equals prevents timing attacks when comparing submitted tokens.
    return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
}
