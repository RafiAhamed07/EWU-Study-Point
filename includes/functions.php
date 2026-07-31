<?php

function validate_ewu_email(string $email): bool
{
    return str_ends_with(strtolower($email), '@std.ewubd.edu');
}

function sanitize_input(string $data): string
{
    $data = trim($data);
    $data = stripslashes($data);

    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect(string $path): void
{
    header("Location: $path");
    exit;
}
