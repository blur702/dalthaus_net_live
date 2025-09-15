<?php
// Force server update
echo "=== FORCING SERVER UPDATE ===\n\n";

// Create a force update script
$forceUpdate = '<?php
// Force update from git
header("Content-Type: text/plain");

echo "=== FORCE UPDATING FROM GIT ===\n\n";

// Reset local changes and pull
$commands = [
    "git reset --hard HEAD" => "Reset local changes",
    "git clean -fd" => "Clean untracked files", 
    "git pull origin main" => "Pull from GitHub"
];

foreach ($commands as $cmd => $desc) {
    echo "$desc...\n";
    exec("cd " . __DIR__ . " && $cmd 2>&1", $output, $returnCode);
    foreach ($output as $line) {
        echo "  $line\n";
    }
    $output = [];
}

// Clear opcache
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "\nOPcache cleared\n";
}

echo "\n✅ Force update complete\n";
?>';

file_put_contents('force_update.php', $forceUpdate);

// Push to GitHub  
exec('git add force_update.php && git commit -m "Add force update script" && git push origin main 2>&1');
echo "1. Pushed force update script\n";

// Execute on server
sleep(2);
echo "\n2. Forcing server update...\n";
$response = @file_get_contents('https://dalthaus.net/force_update.php');
if ($response) {
    echo $response;
}

// Now test the direct login page
echo "\n3. Testing direct login page...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dalthaus.net/direct_login_test.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Direct login test status: $httpCode\n";
if ($httpCode == 200 && strpos($response, 'Direct Login Test') !== false) {
    echo "   ✅ Direct page loads! The issue is in MVC routing\n";
} else {
    echo "   ⚠️ Direct page also has issues\n";
}

echo "\n✅ Test complete\n";
?>