<?php
// Simple session test - safe for production
session_start();

// Set a test value if not already set
if (!isset($_SESSION['test_value'])) {
    $_SESSION['test_value'] = 'Session working at ' . date('Y-m-d H:i:s');
    $status = 'NEW';
} else {
    $status = 'EXISTING';
}

echo "Session Status: $status\n";
echo "Session ID: " . session_id() . "\n";
echo "Test Value: " . ($_SESSION['test_value'] ?? 'NOT SET') . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());

// Clean up
if (isset($_GET['clean'])) {
    session_destroy();
    echo "\nSession destroyed\n";
}
?>