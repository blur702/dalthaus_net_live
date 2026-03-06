<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\MediaUpload;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Upload extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    public function tinymce()
    {
        if (!$this->request->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        $file = $this->request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->renderJson(['error' => 'No file uploaded or an upload error occurred.'], 400);
            return;
        }

        $result = $this->processImageUpload($file, 'tinymce');
        if ($result['success']) {
            $this->renderJson(['location' => $result['location']]);
        } else {
            $this->renderJson(['error' => $result['error']], 400);
        }
    }

    public function dualImage()
    {
        if (!$this->request->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        $results = [];
        $errors = [];

        $displayImage = $this->request->file('display_image');
        if ($displayImage && $displayImage['error'] === UPLOAD_ERR_OK) {
            $result = $this->processImageUpload($displayImage, 'dual_display');
            if ($result['success']) {
                $results['display_image'] = $result['location'];
            } else {
                $errors[] = 'Display image: ' . $result['error'];
            }
        }

        $modalImage = $this->request->file('modal_image');
        if ($modalImage && $modalImage['error'] === UPLOAD_ERR_OK) {
            $result = $this->processImageUpload($modalImage, 'dual_modal');
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

    private function processImageUpload(array $file, string $uploadType): array
    {
        $uploadDir = __DIR__ . '/../../../uploads/content';
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 25 * 1024 * 1024; // 25MB

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File is too large.'];
        }

        $filename = uniqid($uploadType . '_', true) . '.' . $fileExtension;
        $destination = $uploadDir . '/' . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $location = '/uploads/content/' . $filename;

            MediaUpload::track([
                'filename' => $filename,
                'filepath' => $location,
                'original_filename' => $file['name'],
                'file_size' => $file['size'],
                'file_type' => $fileExtension,
                'upload_type' => $uploadType,
                'user_id' => $this->auth->id(),
                'used_in_content' => false
            ]);

            return ['success' => true, 'location' => $location];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }
    }
}
