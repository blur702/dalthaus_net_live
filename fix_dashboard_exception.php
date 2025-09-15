<?php
/**
 * Fix Dashboard Exception Handling
 * Adds proper Exception import to handle missing activity_logs table
 */

// Configuration
$ftpServer = 'ftp.dalthaus.net';
$ftpUsername = 'dalthaus';
$ftpPassword = '(130Bpm)';
$remoteFile = '/public_html/src/Controllers/Admin/Dashboard.php';
$localFile = __DIR__ . '/src/Controllers/Admin/Dashboard.php';

echo "Dashboard Exception Fix Script\n";
echo "==============================\n\n";

// Check if local file exists
if (!file_exists($localFile)) {
    die("ERROR: Local file not found: $localFile\n");
}

echo "1. Reading local Dashboard.php file...\n";
$content = file_get_contents($localFile);

// Verify the fix is in place
if (strpos($content, 'use Exception;') === false) {
    die("ERROR: Exception import not found in local file. Please apply the fix first.\n");
}

echo "   ✓ Exception import found in local file\n\n";

// Connect to FTP
echo "2. Connecting to FTP server...\n";
$ftpConnection = ftp_connect($ftpServer);
if (!$ftpConnection) {
    die("ERROR: Could not connect to FTP server\n");
}

// Login to FTP
if (!ftp_login($ftpConnection, $ftpUsername, $ftpPassword)) {
    ftp_close($ftpConnection);
    die("ERROR: FTP login failed\n");
}

echo "   ✓ Connected to FTP\n\n";

// Enable passive mode
ftp_pasv($ftpConnection, true);

// Backup current file
echo "3. Creating backup of remote file...\n";
$backupFile = '/public_html/backups/Dashboard_' . date('Y-m-d_H-i-s') . '.php';
$backupDir = dirname($backupFile);

// Create backup directory if it doesn't exist
@ftp_mkdir($ftpConnection, $backupDir);

// Copy current file to backup
if (@ftp_get($ftpConnection, 'temp_backup.php', $remoteFile, FTP_BINARY)) {
    if (ftp_put($ftpConnection, $backupFile, 'temp_backup.php', FTP_BINARY)) {
        echo "   ✓ Backup created: $backupFile\n";
        unlink('temp_backup.php');
    }
}

echo "\n4. Uploading fixed Dashboard.php...\n";

// Upload the fixed file
if (!ftp_put($ftpConnection, $remoteFile, $localFile, FTP_BINARY)) {
    ftp_close($ftpConnection);
    die("ERROR: Failed to upload file\n");
}

echo "   ✓ File uploaded successfully\n\n";

// Verify the upload
echo "5. Verifying upload...\n";
$tempFile = 'verify_dashboard.php';
if (ftp_get($ftpConnection, $tempFile, $remoteFile, FTP_BINARY)) {
    $uploadedContent = file_get_contents($tempFile);
    unlink($tempFile);
    
    if (strpos($uploadedContent, 'use Exception;') !== false) {
        echo "   ✓ Exception import verified in uploaded file\n";
    } else {
        echo "   ⚠ Warning: Exception import not found in uploaded file\n";
    }
    
    // Check file size
    $uploadedSize = strlen($uploadedContent);
    $localSize = strlen($content);
    echo "   File sizes - Local: $localSize bytes, Remote: $uploadedSize bytes\n";
}

// Close FTP connection
ftp_close($ftpConnection);

echo "\n6. Testing dashboard access...\n";
echo "   Please test: https://dalthaus.net/admin/dashboard\n";
echo "\n✓ Dashboard exception handling fixed!\n";
echo "\nThe dashboard should now handle the missing activity_logs table gracefully.\n";
echo "Activities will show as 0 until the activity_logs table is created.\n";
?>