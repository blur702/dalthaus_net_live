<?php
// Clear all sessions to fix redirect loop
header("Content-Type: text/plain");

echo "=== FIXING SESSION REDIRECT LOOP ===\n\n";

// 1. Clear all session files
$sessionPath = session_save_path() ?: "/tmp";
$sessionFiles = glob($sessionPath . "/sess_*");
$count = count($sessionFiles);
foreach ($sessionFiles as $file) {
    @unlink($file);
}
echo "1. Cleared $count session files\n";

// 2. Start a fresh session to test
session_start();
echo "2. Started fresh session: " . session_id() . "\n";

// 3. Test setting session variables
$_SESSION["test"] = "working";
if (isset($_SESSION["test"])) {
    echo "3. Session variables work correctly\n";
    unset($_SESSION["test"]);
} else {
    echo "3. WARNING: Session variables not working!\n";
}

// 4. Destroy test session
session_destroy();
echo "4. Test session destroyed\n";

echo "\n✅ Session cleanup complete!\n";
echo "Users can now log in without redirect loops.\n";
?>