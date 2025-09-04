<?php
// Basic authentication helpers
if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!isAdmin()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}
