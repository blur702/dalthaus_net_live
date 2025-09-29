<?php
// Simple uploader to get the check_users.php file onto the server
if (!file_exists('check_users.php')) {
    $content = '<?php
$config = [
    "host" => "localhost",
    "dbname" => "dalthaus_maincms",
    "username" => "dalthaus_maincms",
    "password" => "f4!,Wpds=w6*=~+1"
];

try {
    $pdo = new PDO("mysql:host={$config[\"host\"]};dbname={$config[\"dbname\"]};charset=utf8mb4", $config[\"username\"], $config[\"password\"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

    echo "<h1>Production User Check</h1>";
    $result = $pdo->query("SELECT user_id, username, email, password_hash, is_admin FROM users ORDER BY user_id");
    $users = $result->fetchAll();

    echo "<table border=\"1\"><tr><th>ID</th><th>Username</th><th>Password Valid</th></tr>";
    foreach ($users as $user) {
        $isValid = password_verify("(130Bpm)", $user["password_hash"]);
        echo "<tr><td>{$user[\"user_id\"]}</td><td>" . htmlspecialchars($user["username"]) . "</td><td>" . ($isValid ? "✅ YES" : "❌ NO") . "</td></tr>";
    }
    echo "</table>";

    echo "<p><strong>Delete this file after use.</strong></p>";
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>';

    file_put_contents('check_users.php', $content);
    echo "Created check_users.php - <a href='check_users.php'>Run it</a>";
} else {
    echo "File exists - <a href='check_users.php'>Run check_users.php</a>";
}
?>