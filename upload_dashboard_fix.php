<?php
/**
 * Upload Dashboard Fix via cURL FTP
 */

$localFile = __DIR__ . '/src/Controllers/Admin/Dashboard.php';
$remoteFile = '/public_html/src/Controllers/Admin/Dashboard.php';

echo "Uploading Dashboard.php with exception handling fix...\n\n";

// Check if local file exists
if (!file_exists($localFile)) {
    die("ERROR: Local file not found: $localFile\n");
}

// Read the file
$fileContent = file_get_contents($localFile);

// Verify fix is in place
if (strpos($fileContent, 'use Exception;') === false) {
    die("ERROR: Exception import not found in local file\n");
}

echo "✓ Exception import found in local file\n";
echo "Uploading to remote server...\n";

// Setup cURL for FTP upload
$ch = curl_init();
$fp = fopen($localFile, 'r');

curl_setopt($ch, CURLOPT_URL, "ftp://ftp.dalthaus.net" . $remoteFile);
curl_setopt($ch, CURLOPT_USERPWD, "dalthaus:(130Bpm)");
curl_setopt($ch, CURLOPT_UPLOAD, 1);
curl_setopt($ch, CURLOPT_INFILE, $fp);
curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFile));
curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute upload
$result = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);
fclose($fp);

if ($result) {
    echo "\n✓ Dashboard.php uploaded successfully!\n";
    echo "\nThe dashboard should now work properly.\n";
    echo "Test it at: https://dalthaus.net/admin/dashboard\n";
} else {
    echo "\n✗ Upload failed: $error\n";
}
?>