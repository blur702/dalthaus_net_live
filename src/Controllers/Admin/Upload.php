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

    public function dualImage()
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        $uploadDir = __DIR__ . '/../../../uploads/content';
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 25 * 1024 * 1024; // 25MB

        $results = [];
        $errors = [];

        // Process display image
        if (!empty($_FILES['display_image']) && $_FILES['display_image']['error'] === UPLOAD_ERR_OK) {
            $result = $this->processImageUpload($_FILES['display_image'], $uploadDir, $allowedTypes, $maxSize, 'display');
            if ($result['success']) {
                $results['display_image'] = $result['location'];
            } else {
                $errors[] = 'Display image: ' . $result['error'];
            }
        }

        // Process modal image
        if (!empty($_FILES['modal_image']) && $_FILES['modal_image']['error'] === UPLOAD_ERR_OK) {
            $result = $this->processImageUpload($_FILES['modal_image'], $uploadDir, $allowedTypes, $maxSize, 'modal');
            if ($result['success']) {
                $results['modal_image'] = $result['location'];
            } else {
                $errors[] = 'Modal image: ' . $result['error'];
            }
        }

        if (empty($results) && empty($errors)) {
            $this->renderJson(['error' => 'No valid images uploaded.'], 400);
            return;
        }

        if (!empty($errors)) {
            $this->renderJson(['error' => implode(', ', $errors), 'partial_results' => $results], 400);
            return;
        }

        $this->renderJson(['success' => true, 'images' => $results]);
    }

    private function processImageUpload(array $file, string $uploadDir, array $allowedTypes, int $maxSize, string $prefix): array
    {
        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type.'];
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File is too large.'];
        }

        // Create a unique filename with prefix
        $filename = uniqid($prefix . '_', true) . '.' . $fileExtension;
        $destination = $uploadDir . '/' . $filename;

        // Ensure the upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'location' => '/uploads/content/' . $filename];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }
    }
}