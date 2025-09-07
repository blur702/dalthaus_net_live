<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Settings as SettingsModel;
use Exception;

/**
 * Admin Settings Controller
 * 
 * Handles site settings management including file uploads
 * for logo and favicon, and general configuration updates.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Settings extends BaseController
{
    /**
     * Maximum file size for uploads (25MB)
     */
    private const MAX_FILE_SIZE = 25 * 1024 * 1024;

    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        $this->requireAuth();
        $this->view->layout('admin');
    }

    /**
     * Display and manage site settings
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Get all settings for admin form
            $settings = SettingsModel::getForAdmin();
            
            // Get form errors and data from session (from validation failures)
            $formErrors = $_SESSION['form_errors'] ?? [];
            $formData = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_errors'], $_SESSION['form_data']);

            // Merge form data with existing settings for display
            $displayData = array_merge($settings, $formData);

            // Get available options for select fields
            $timezones = SettingsModel::getAvailableTimezones();
            $dateFormats = SettingsModel::getAvailableDateFormats();

            $this->render('admin/settings/index', [
                'settings' => $displayData,
                'timezones' => $timezones,
                'date_formats' => $dateFormats,
                'form_errors' => $formErrors,
                'flash' => $this->getFlash(),
                'page_title' => 'Site Settings',
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            $this->logError('Settings index error', $e);
            $this->setFlash('error', 'An error occurred while loading settings.');
            $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Update site settings
     * 
     * @return void
     */
    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/settings');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/settings');
        }

        try {
            // Get form data
            $data = $this->getFormData();
            
            // Validate settings data
            $errors = SettingsModel::validateSettings($data);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/settings');
            }

            // Handle file uploads
            $this->handleFileUploads($data);

            // Save settings to database
            if (SettingsModel::setMultiple($data)) {
                $this->setFlash('success', 'Settings updated successfully.');
                
                // Update timezone if changed
                if (isset($data['timezone'])) {
                    date_default_timezone_set($data['timezone']);
                }
                
                $this->redirect('/admin/settings');
            } else {
                throw new Exception('Failed to save settings');
            }

        } catch (Exception $e) {
            $this->logError('Settings update error', $e);
            $this->setFlash('error', 'An error occurred while updating settings.');
            $this->redirect('/admin/settings');
        }
    }

    /**
     * Upload logo file via AJAX
     * 
     * @return void
     */
    public function uploadLogo(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                $this->renderJson(['success' => false, 'message' => 'No file uploaded or upload error'], 400);
            }

            $file = $_FILES['logo'];
            
            // Validate file
            $validation = $this->validateImageFile($file);
            if (!$validation['valid']) {
                $this->renderJson(['success' => false, 'message' => $validation['message']], 400);
            }

            // Upload file
            $uploadDir = __DIR__ . '/../../../uploads/settings';
            $filename = $this->uploadSettingsFile($file, $uploadDir, 'logo');
            
            if ($filename === false) {
                $this->renderJson(['success' => false, 'message' => 'Failed to upload logo'], 500);
            }

            // Update setting in database
            if (SettingsModel::set('site_logo', $filename)) {
                $this->renderJson([
                    'success' => true,
                    'message' => 'Logo uploaded successfully',
                    'filename' => $filename,
                    'url' => '/uploads/settings/' . $filename
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to save logo setting'], 500);
            }

        } catch (Exception $e) {
            $this->logError('Logo upload error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while uploading logo'], 500);
        }
    }

    /**
     * Upload favicon file via AJAX
     * 
     * @return void
     */
    public function uploadFavicon(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            if (!isset($_FILES['favicon']) || $_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
                $this->renderJson(['success' => false, 'message' => 'No file uploaded or upload error'], 400);
            }

            $file = $_FILES['favicon'];
            
            // Validate file (favicon can be .ico, .png, .gif)
            $validation = $this->validateIconFile($file);
            if (!$validation['valid']) {
                $this->renderJson(['success' => false, 'message' => $validation['message']], 400);
            }

            // Upload file
            $uploadDir = __DIR__ . '/../../../uploads/settings';
            $filename = $this->uploadSettingsFile($file, $uploadDir, 'favicon');
            
            if ($filename === false) {
                $this->renderJson(['success' => false, 'message' => 'Failed to upload favicon'], 500);
            }

            // Update setting in database
            if (SettingsModel::set('favicon', $filename)) {
                $this->renderJson([
                    'success' => true,
                    'message' => 'Favicon uploaded successfully',
                    'filename' => $filename,
                    'url' => '/uploads/settings/' . $filename
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to save favicon setting'], 500);
            }

        } catch (Exception $e) {
            $this->logError('Favicon upload error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while uploading favicon'], 500);
        }
    }

    /**
     * Remove logo file
     * 
     * @return void
     */
    public function removeLogo(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $currentLogo = SettingsModel::get('site_logo');
            
            // Remove file from filesystem
            if (!empty($currentLogo)) {
                $filePath = __DIR__ . '/../../../uploads/settings/' . $currentLogo;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Update setting in database
            if (SettingsModel::set('site_logo', '')) {
                $this->renderJson([
                    'success' => true,
                    'message' => 'Logo removed successfully'
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to remove logo setting'], 500);
            }

        } catch (Exception $e) {
            $this->logError('Logo removal error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while removing logo'], 500);
        }
    }

    /**
     * Remove favicon file
     * 
     * @return void
     */
    public function removeFavicon(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $currentFavicon = SettingsModel::get('favicon');
            
            // Remove file from filesystem
            if (!empty($currentFavicon)) {
                $filePath = __DIR__ . '/../../../uploads/settings/' . $currentFavicon;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Update setting in database
            if (SettingsModel::set('favicon', '')) {
                $this->renderJson([
                    'success' => true,
                    'message' => 'Favicon removed successfully'
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to remove favicon setting'], 500);
            }

        } catch (Exception $e) {
            $this->logError('Favicon removal error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while removing favicon'], 500);
        }
    }

    /**
     * Clear settings cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            SettingsModel::clearCache();
            
            $this->renderJson([
                'success' => true,
                'message' => 'Settings cache cleared successfully'
            ]);

        } catch (Exception $e) {
            $this->logError('Cache clear error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while clearing cache'], 500);
        }
    }

    /**
     * Export settings as JSON
     * 
     * @return void
     */
    public function export(): void
    {
        try {
            $settings = SettingsModel::getAll();
            
            // Set headers for JSON download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="settings_export_' . date('Y-m-d_H-i-s') . '.json"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo json_encode($settings, JSON_PRETTY_PRINT);
            exit;

        } catch (Exception $e) {
            $this->logError('Settings export error', $e);
            $this->setFlash('error', 'An error occurred while exporting settings.');
            $this->redirect('/admin/settings');
        }
    }

    /**
     * Import settings from JSON
     * 
     * @return void
     */
    public function import(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/settings');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/settings');
        }

        try {
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                $this->setFlash('error', 'No file uploaded or upload error.');
                $this->redirect('/admin/settings');
            }

            $file = $_FILES['import_file'];
            
            // Validate file type
            if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
                $this->setFlash('error', 'Invalid file type. Please upload a JSON file.');
                $this->redirect('/admin/settings');
            }

            // Read and parse JSON
            $json = file_get_contents($file['tmp_name']);
            $settings = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->setFlash('error', 'Invalid JSON file format.');
                $this->redirect('/admin/settings');
            }

            if (!is_array($settings)) {
                $this->setFlash('error', 'Invalid settings format in JSON file.');
                $this->redirect('/admin/settings');
            }

            // Filter and validate settings
            $validSettings = [];
            $allowedSettings = array_keys(SettingsModel::getForAdmin());
            
            foreach ($settings as $key => $value) {
                if (in_array($key, $allowedSettings)) {
                    $validSettings[$key] = $value;
                }
            }

            if (empty($validSettings)) {
                $this->setFlash('error', 'No valid settings found in the imported file.');
                $this->redirect('/admin/settings');
            }

            // Validate settings data
            $errors = SettingsModel::validateSettings($validSettings);
            
            if (!empty($errors)) {
                $this->setFlash('error', 'Imported settings contain validation errors: ' . implode(', ', $errors));
                $this->redirect('/admin/settings');
            }

            // Import settings
            if (SettingsModel::setMultiple($validSettings)) {
                $count = count($validSettings);
                $this->setFlash('success', "Successfully imported {$count} settings.");
            } else {
                $this->setFlash('error', 'Failed to import settings.');
            }

        } catch (Exception $e) {
            $this->logError('Settings import error', $e);
            $this->setFlash('error', 'An error occurred while importing settings.');
        }

        $this->redirect('/admin/settings');
    }

    /**
     * Reset settings to defaults
     * 
     * @return void
     */
    public function reset(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/settings');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/settings');
        }

        try {
            $defaults = SettingsModel::getForAdmin(); // This returns defaults if settings don't exist
            
            // Reset to defaults (empty values for file uploads)
            $resetSettings = [
                'site_title' => 'My CMS',
                'site_motto' => 'A Simple Content Management System',
                'site_logo' => '',
                'favicon' => '',
                'admin_email' => 'admin@example.com',
                'timezone' => 'America/New_York',
                'date_format' => 'Y-m-d',
                'items_per_page' => '10'
            ];

            if (SettingsModel::setMultiple($resetSettings)) {
                // Clear cache
                SettingsModel::clearCache();
                
                $this->setFlash('success', 'Settings have been reset to defaults.');
            } else {
                $this->setFlash('error', 'Failed to reset settings.');
            }

        } catch (Exception $e) {
            $this->logError('Settings reset error', $e);
            $this->setFlash('error', 'An error occurred while resetting settings.');
        }

        $this->redirect('/admin/settings');
    }

    /**
     * Get form data from POST request
     * 
     * @return array
     */
    private function getFormData(): array
    {
        return [
            'site_title' => $this->sanitize($this->getParam('site_title', '', 'post')),
            'site_motto' => $this->sanitize($this->getParam('site_motto', '', 'post')),
            'admin_email' => $this->sanitize($this->getParam('admin_email', '', 'post')),
            'timezone' => $this->sanitize($this->getParam('timezone', '', 'post')),
            'date_format' => $this->sanitize($this->getParam('date_format', '', 'post')),
            'items_per_page' => $this->sanitize($this->getParam('items_per_page', '10', 'post')),
            'maintenance_mode' => $this->getParam('maintenance_mode', '0', 'post') === '1' ? '1' : '0',
            'maintenance_message' => $this->sanitize($this->getParam('maintenance_message', '', 'post'))
        ];
    }

    /**
     * Handle file uploads for settings (logo and favicon)
     * 
     * @param array &$data Form data (passed by reference)
     * @return void
     */
    private function handleFileUploads(array &$data): void
    {
        $uploadDir = __DIR__ . '/../../../uploads/settings';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $validation = $this->validateImageFile($_FILES['site_logo']);
            if ($validation['valid']) {
                $filename = $this->uploadSettingsFile($_FILES['site_logo'], $uploadDir, 'logo');
                if ($filename !== false) {
                    $data['site_logo'] = $filename;
                }
            }
        }

        // Handle favicon upload
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $validation = $this->validateIconFile($_FILES['favicon']);
            if ($validation['valid']) {
                $filename = $this->uploadSettingsFile($_FILES['favicon'], $uploadDir, 'favicon');
                if ($filename !== false) {
                    $data['favicon'] = $filename;
                }
            }
        }
    }

    /**
     * Validate image file for logo
     * 
     * @param array $file File array from $_FILES
     * @return array Validation result
     */
    private function validateImageFile(array $file): array
    {
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'File size must be less than 25MB'];
        }

        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
        }

        // Additional MIME type check for security
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'message' => 'Invalid file content'];
        }

        return ['valid' => true, 'message' => 'File is valid'];
    }

    /**
     * Validate icon file for favicon
     * 
     * @param array $file File array from $_FILES
     * @return array Validation result
     */
    private function validateIconFile(array $file): array
    {
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'File size must be less than 25MB'];
        }

        // Check file type (favicon specific)
        $allowedTypes = ['ico', 'png', 'gif', 'svg'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
        }

        // Additional MIME type check for security
        $allowedMimes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/gif', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Allow generic types for .ico files as they might not be detected properly
        if ($extension === 'ico' && !in_array($mimeType, $allowedMimes)) {
            // Less strict validation for .ico files
            if (!in_array($mimeType, ['application/octet-stream', 'image/x-icon', 'image/vnd.microsoft.icon'])) {
                return ['valid' => false, 'message' => 'Invalid icon file content'];
            }
        } elseif ($extension !== 'ico' && !in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'message' => 'Invalid file content'];
        }

        return ['valid' => true, 'message' => 'File is valid'];
    }

    /**
     * Upload settings file with proper naming
     * 
     * @param array $file File array from $_FILES
     * @param string $uploadDir Upload directory
     * @param string $type File type (logo or favicon)
     * @return string|false Uploaded filename or false on error
     */
    private function uploadSettingsFile(array $file, string $uploadDir, string $type): string|false
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $type . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . '/' . $filename;
        
        // Remove old file if it exists
        $oldFile = SettingsModel::get('site_' . $type);
        if (!empty($oldFile)) {
            $oldPath = $uploadDir . '/' . $oldFile;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $filename;
        }
        
        return false;
    }
}
