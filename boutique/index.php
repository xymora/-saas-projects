<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isOwnerOrAdmin()) {
        header('Location: ' . BASE_URL . '/views/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/views/pos.php');
    }
} else {
    header('Location: ' . BASE_URL . '/views/login.php');
}
exit;
