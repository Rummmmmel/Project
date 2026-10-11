<?php
// LOGIN GUARD - put at the very top of every page that needs a logged-in user.
//
//   require_once __DIR__ . '/../includes/auth.php';
//   require_login();              // any logged-in user
//   require_login('admin');       // admin only

function require_login(?string $role = null): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('You are not allowed to view this page.');
    }
}
