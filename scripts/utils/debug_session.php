<?php
/**
 * Debug endpoint for testing authentication and sessions
 */

// Start session
session_start();

// Set content type
header('Content-Type: application/json');

// Debug information
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>