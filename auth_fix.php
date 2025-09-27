<?php
// Simple authentication diagnostic and fix
$config = ['host' => 'localhost', 'dbname' => 'dalthaus_maincms', 'username' => 'dalthaus_maincms', 'password' => 'f4!,Wpds=w6*=~+1'];

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "<h1>Authentication Fix</h1>";

    // Get kevin user
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = 'kevin'");
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        $testPassword = '(130Bpm)';
        $isValid = password_verify($testPassword, $user['password_hash']);

        echo "<p>User: {$user['username']} (ID: {$user['user_id']})</p>";
        echo "<p>Password test: " . ($isValid ? "✅ VALID" : "❌ INVALID") . "</p>";

        if (!$isValid) {
            echo "<p>Fixing password...</p>";
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'kevin'");
            $updateStmt->execute([$newHash]);
            echo "<p>✅ Password updated! <a href='/admin/login'>Test Login</a></p>";
        }
    } else {
        echo "<p>❌ User kevin not found</p>";
    }

    echo "<p><strong>Delete this file!</strong></p>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>