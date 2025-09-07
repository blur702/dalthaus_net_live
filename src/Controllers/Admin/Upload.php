<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;

class Upload extends BaseController
{
    public function tinymce()
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->renderJson(['error' => 'No file uploaded or an upload error occurred.'], 400);
            return;
        }

        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../../../uploads/content';
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 25 * 1024 * 1024; // 25MB

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            $this->renderJson(['error' => 'Invalid file type.'], 400);
            return;
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            $this->renderJson(['error' => 'File is too large.'], 400);
            return;
        }

        // Create a unique filename
        $filename = uniqid('img_', true) . '.' . $fileExtension;
        $destination = $uploadDir . '/' . $filename;

        // Ensure the upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $location = '/uploads/content/' . $filename;
            $this->renderJson(['location' => $location]);
        } else {
            $this->renderJson(['error' => 'Failed to move uploaded file.'], 500);
        }
    }
}