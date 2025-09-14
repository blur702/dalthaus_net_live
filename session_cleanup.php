<?php
// Clean up ALL sessions to start fresh
header("Content-Type: text/plain");

echo "=== SESSION CLEANUP ===\n\n";

// 1. Clear session directory
$sessionPath = session_save_path() ?: "/opt/alt/php84/var/lib/php/session";
$files = glob($sessionPath . "/sess_*");
$count = 0;
foreach ($files as $file) {
    if (@unlink($file)) $count++;
}
echo "Cleared $count session files from $sessionPath\n";

// 2. Clear any alternative session paths
$altPaths = [
    "/tmp",
    "/var/lib/php/sessions",
    "/var/lib/php/session",
    "/opt/alt/php74/var/lib/php/session",
    "/opt/alt/php80/var/lib/php/session",
    "/opt/alt/php81/var/lib/php/session",
    "/opt/alt/php82/var/lib/php/session",
    "/opt/alt/php83/var/lib/php/session",
    "/opt/alt/php84/var/lib/php/session"
];

foreach ($altPaths as $path) {
    if (is_dir($path)) {
        $files = glob($path . "/sess_*");
        if (!empty($files)) {
            echo "Found " . count($files) . " session files in $path\n";
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}

// 3. Reset opcache if available
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "OPcache reset\n";
}

echo "\n✅ Cleanup complete\n";
?>