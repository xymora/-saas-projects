<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/views/login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_rol'], $roles, true)) {
        header('Location: ' . BASE_URL . '/views/403.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id']     ?? 0,
        'nombre' => $_SESSION['user_nombre'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
        'rol'    => $_SESSION['user_rol']    ?? '',
    ];
}

function hasRole(string $rol): bool {
    return ($_SESSION['user_rol'] ?? '') === $rol;
}

function isOwnerOrAdmin(): bool {
    return in_array($_SESSION['user_rol'] ?? '', ['dueno', 'admin'], true);
}

function isAdmin(): bool {
    return ($_SESSION['user_rol'] ?? '') === 'admin';
}
