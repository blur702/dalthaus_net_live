<?php
/**
 * TinyMCE Image Upload Handler
 * Handles image uploads from TinyMCE editor
 * Images are stored untouched with no processing
 */

require_once '../includes/auth.php';
require_once '../includes/config.php';

// Require admin authentication
Auth::requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

// Check if file was uploaded
if (\!isset($_FILES['file']) || $_FILES['file']['error'] \!== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$uploadedFile = $_FILES['file'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
finfo_close($finfo);

if (\!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.']);
    exit;
}

// Create upload directory structure (YYYY/MM)
$year = date('Y');
$month = date('m');
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $year . '/' . $month;

// Create directories if they don't exist
if (\!is_dir($uploadDir)) {
    if (\!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create upload directory']);
        exit;
    }
}

// Generate unique filename
$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . '/' . $filename;

// Move uploaded file (no processing, store as-is)
if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
    // Return the URL for TinyMCE
    $imageUrl = '/uploads/' . $year . '/' . $month . '/' . $filename;
    
    echo json_encode([
        'location' => $imageUrl
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
?>