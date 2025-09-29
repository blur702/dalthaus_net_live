<?php
/**
 * Fix kevin user password in production
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

    echo "<h1>Fix Kevin User Password</h1>\n";

    // Get current kevin user
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = 'kevin'");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo "<p style='color: red;'>❌ User 'kevin' not found!</p>\n";
        exit;
    }

    echo "<p>Found user: {$user['username']} (ID: {$user['user_id']})</p>\n";
    echo "<p>Current hash: " . htmlspecialchars(substr($user['password_hash'], 0, 30)) . "...</p>\n";

    // Test current password
    $testPassword = '(130Bpm)';
    $isCurrentValid = password_verify($testPassword, $user['password_hash']);
    echo "<p>Current password test: " . ($isCurrentValid ? "✅ VALID" : "❌ INVALID") . "</p>\n";

    if (!$isCurrentValid) {
        // Generate new hash
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<p>Generating new hash for password '(130Bpm)'...</p>\n";

        // Update password
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'kevin'");
        $updateStmt->execute([$newHash]);

        echo "<p style='color: green;'>✅ Password updated successfully!</p>\n";

        // Verify the update
        $verifyStmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'kevin'");
        $verifyStmt->execute();
        $newUser = $verifyStmt->fetch();

        $isNewValid = password_verify($testPassword, $newUser['password_hash']);
        echo "<p>Verification test: " . ($isNewValid ? "✅ NEW PASSWORD WORKS" : "❌ UPDATE FAILED") . "</p>\n";

        if ($isNewValid) {
            echo "<h2>SUCCESS!</h2>\n";
            echo "<p>User 'kevin' can now log in with password '(130Bpm)'</p>\n";
            echo "<p><a href='/admin/login'>Test Login Now</a></p>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ Password is already correct. Login should work.</p>\n";
    }

    echo "<p><strong>IMPORTANT:</strong> Delete this file for security.</p>\n";

} catch (Exception $e) {
    echo "<h1>ERROR</h1>\n";
    echo "<p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>