<?php
// Debug authentication issue on production server
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $config = ['host' => 'localhost', 'dbname' => 'dalthaus_maincms', 'username' => 'dalthaus_maincms', 'password' => 'f4!,Wpds=w6*=~+1'];
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "<h1>Authentication Debug on Production Server</h1>";

    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = $pdo->query("DESCRIBE users");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($result as $row) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";

    // Check for user 'kevin'
    echo "<h3>User 'kevin' Details:</h3>";
    $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin, created_at FROM users WHERE username = ?");
    $stmt->execute(['kevin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            if ($key === 'password_hash') {
                $value = substr($value, 0, 20) . '... (truncated for security)';
            }
            echo "<tr><td>$key</td><td>$value</td></tr>";
        }
        echo "</table>";

        // Test password verification
        echo "<h3>Password Verification Test:</h3>";
        $test_password = '(130Bpm)';
        $stored_hash = $user['password_hash'];

        echo "<p>Testing password: '$test_password'</p>";
        echo "<p>Hash algorithm: " . (str_starts_with($stored_hash, '$2y$') ? 'bcrypt' : 'unknown') . "</p>";

        if (password_verify($test_password, $stored_hash)) {
            echo "<p>✅ Password verification SUCCESSFUL</p>";
        } else {
            echo "<p>❌ Password verification FAILED</p>";

            // Try creating a new hash for comparison
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<p>New hash would be: " . substr($new_hash, 0, 30) . "...</p>";

            // Check if maybe it's a plain text password (security issue but possible)
            if ($stored_hash === $test_password) {
                echo "<p>⚠️ Password is stored as PLAIN TEXT (major security issue)</p>";
            } else if (md5($test_password) === $stored_hash) {
                echo "<p>⚠️ Password is MD5 hashed (weak security)</p>";
            } else if (sha1($test_password) === $stored_hash) {
                echo "<p>⚠️ Password is SHA1 hashed (weak security)</p>";
            }
        }

    } else {
        echo "<p>❌ User 'kevin' NOT FOUND in database</p>";

        // Show all users
        echo "<h3>All Users in Database:</h3>";
        $result = $pdo->query("SELECT id, username, is_admin, created_at FROM users ORDER BY id");
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Is Admin</th><th>Created At</th></tr>";
        foreach ($result as $row) {
            echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['is_admin']}</td><td>{$row['created_at']}</td></tr>";
        }
        echo "</table>";
    }

    // Check session configuration
    echo "<h3>Session Configuration:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>session.name</td><td>" . session_name() . "</td></tr>";
    echo "<tr><td>session.cookie_lifetime</td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
    echo "<tr><td>session.cookie_httponly</td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
    echo "<tr><td>session.cookie_secure</td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
    echo "<tr><td>session.cookie_samesite</td><td>" . ini_get('session.cookie_samesite') . "</td></tr>";
    echo "</table>";

    echo "<p><strong>Delete this file after debugging!</strong></p>";

} catch (Exception $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>