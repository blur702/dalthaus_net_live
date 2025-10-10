<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Settings as SettingsModel;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Settings extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        $this->view->layout('admin');
    }

    public function index(): void
    {
        $this->render('admin/settings/index', [
            'settings' => SettingsModel::getForAdmin(),
            'timezones' => SettingsModel::getAvailableTimezones(),
            'date_formats' => SettingsModel::getAvailableDateFormats(),
            'page_title' => 'Site Settings',
        ]);
    }

    public function update(): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/settings');
            return;
        }

        try {
            $data = $this->getFormData();
            $errors = SettingsModel::validateSettings($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('; ', $errors));
                $this->redirect('/admin/settings');
                return;
            }

            $this->handleFileUploads($data);

            SettingsModel::setMultiple($data);
            $this->setFlash('success', 'Settings updated successfully.');

        } catch (Exception $e) {
            $this->logError('Settings update error', $e);
            $this->setFlash('error', 'An error occurred while updating settings.');
        }

        $this->redirect('/admin/settings');
    }

    private function getFormData(): array
    {
        return [
            'site_title' => $this->request->post('site_title', ''),
            'site_motto' => $this->request->post('site_motto', ''),
            'admin_email' => $this->request->post('admin_email', ''),
            'timezone' => $this->request->post('timezone', ''),
            'date_format' => $this->request->post('date_format', ''),
            'items_per_page' => $this->request->post('items_per_page', '10'),
            'maintenance_mode' => $this->request->post('maintenance_mode', '0') === '1' ? '1' : '0',
            'maintenance_message' => $this->request->post('maintenance_message', '')
        ];
    }

    private function handleFileUploads(array &$data): void
    {
        $uploadDir = __DIR__ . '/../../../uploads/settings';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $logo = $this->request->file('site_logo');
        if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
            $filename = $this->uploadSettingsFile($logo, $uploadDir, 'logo');
            if ($filename) {
                $data['site_logo'] = $filename;
            }
        }

        $favicon = $this->request->file('favicon');
        if ($favicon && $favicon['error'] === UPLOAD_ERR_OK) {
            $filename = $this->uploadSettingsFile($favicon, $uploadDir, 'favicon');
            if ($filename) {
                $data['favicon'] = $filename;
            }
        }
    }

    private function uploadSettingsFile(array $file, string $uploadDir, string $type): ?string
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $type . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . '/' . $filename;

        $oldFile = SettingsModel::get('site_' . $type);
        if ($oldFile) {
            $oldPath = $uploadDir . '/' . $oldFile;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $filename;
        }
        return null;
    }
}