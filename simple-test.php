<?php
// Minimal test page - no database required
echo "<h1>Simple Test Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script: " . __FILE__ . "</p>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>File Check</h2>";
$files = [
    'index.php',
    'includes/config.php',
    'includes/config.local.php',
    'admin/login.php',
    'assets/css/public.css'
];

echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file exists (" . filesize($file) . " bytes)</li>";
    } else {
        echo "<li>❌ $file not found</li>";
    }
}
echo "</ul>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='/'>Homepage (may error)</a></li>";
echo "<li><a href='/admin/login.php'>Admin Login</a></li>";
echo "<li><a href='/remote-file-agent.php?action=info&token=agent-" . date('Ymd') . "'>File Agent Info</a></li>";
echo "</ul>";
?>