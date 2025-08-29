<?php
// Simple git pull script
$token = $_GET['token'] ?? '';
if ($token !== 'pull-' . date('Ymd')) {
    die('Invalid token. Use: pull-' . date('Ymd'));
}

header('Content-Type: text/plain');
chdir('/home/dalthaus/public_html');
echo "Pulling latest changes...\n";
echo shell_exec('git pull origin main 2>&1');
echo "\nCurrent status:\n";
echo shell_exec('git log -1 --oneline');
?>