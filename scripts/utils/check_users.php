<?php
/**
 * Check production users and test password verification
 */

// Load config
$config = [
    'host' => 'localhost',
    'dbname' => 'dalthaus_maincms',
    'username' => 'dalthaus_maincms',
    'password' => 'f4!,Wpds=w6*=~+1'
];

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<h1>Production User Check</h1>\n";

    // Get all users
    $result = $pdo->query("SELECT user_id, username, email, password_hash, is_admin, created_at FROM users ORDER BY user_id");
    $users = $result->fetchAll();

    echo "<h2>All Users:</h2>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Hash</th><th>Is Admin</th><th>Created</th></tr>\n";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . substr($user['password_hash'], 0, 20) . "...</td>";
        echo "<td>{$user['is_admin']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // Test password verification for user 'kevin'
    $kevinUser = null;
    foreach ($users as $user) {
        if ($user['username'] === 'kevin') {
            $kevinUser = $user;
            break;
        }
    }

    echo "<h2>Password Verification Test:</h2>\n";
    if ($kevinUser) {
        echo "<p>Found user 'kevin' with ID: {$kevinUser['user_id']}</p>\n";
        echo "<p>Password hash: " . htmlspecialchars($kevinUser['password_hash']) . "</p>\n";

        // Test password verification
        $testPassword = '(130Bpm)';
        $isValid = password_verify($testPassword, $kevinUser['password_hash']);

        echo "<p>Testing password '(130Bpm)': " . ($isValid ? "✅ VALID" : "❌ INVALID") . "</p>\n";

        if (!$isValid) {
            echo "<p style='color: red;'>❌ The password '(130Bpm)' does NOT match the stored hash!</p>\n";
            echo "<p>This explains why authentication is failing.</p>\n";

            // Generate a new hash for the correct password
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "<p>Correct hash for '(130Bpm)' would be: " . htmlspecialchars($newHash) . "</p>\n";

            echo "<h3>To fix this, run:</h3>\n";
            echo "<code>UPDATE users SET password_hash = '" . htmlspecialchars($newHash) . "' WHERE username = 'kevin';</code>\n";
        } else {
            echo "<p style='color: green;'>✅ Password is correct - there must be another issue.</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ User 'kevin' not found in database!</p>\n";
        echo "<p>Available usernames: ";
        foreach ($users as $user) {
            echo "'" . htmlspecialchars($user['username']) . "' ";
        }
        echo "</p>\n";
    }

    echo "<p><strong>IMPORTANT:</strong> Delete this file for security.</p>\n";

} catch (Exception $e) {
    echo "<h1>ERROR</h1>\n";
    echo "<p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>