<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\User as UserModel;
use CMS\Utils\Auth as AuthUtil;
use Exception;

/**
 * Admin Users Controller
 * 
 * Handles user management including CRUD operations,
 * pagination, search, and bulk operations for admin users.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Users extends BaseController
{
    /**
     * Items per page for pagination
     */
    private const ITEMS_PER_PAGE = 20;

    /**
     * Authentication utility
     */
    private AuthUtil $auth;

    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        $this->requireAuth();
        $this->view->layout('admin');
        $this->auth = new AuthUtil($this->db, $this->config['security']);
    }

    /**
     * List all users with pagination and search
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Get filter parameters
            $page = max(1, (int) $this->getParam('page', 1));
            $search = $this->sanitize($this->getParam('search', ''));
            $sortBy = $this->getParam('sort_by', 'created_at');
            $sortDir = strtoupper($this->getParam('sort_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            // Build filters array
            $filters = array_filter([
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir
            ]);

            // Calculate pagination
            $offset = ($page - 1) * self::ITEMS_PER_PAGE;
            
            // Get users and total count
            $users = UserModel::getForAdmin($filters, self::ITEMS_PER_PAGE, $offset);
            $totalCount = UserModel::countForAdmin($filters);
            $totalPages = (int) ceil($totalCount / self::ITEMS_PER_PAGE);

            // Prepare pagination data
            $pagination = [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalCount,
                'per_page' => self::ITEMS_PER_PAGE,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ];

            $this->render('admin/users/index', [
                'users' => $users,
                'filters' => $filters,
                'pagination' => $pagination,
                'current_user_id' => $this->getCurrentUserId(),
                'flash' => $this->getFlash(),
                'page_title' => 'User Management',
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            $this->logError('Users index error', $e);
            $this->setFlash('error', 'An error occurred while loading users.');
            $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Show create user form
     * 
     * @return void
     */
    public function create(): void
    {
        // Get form errors and data from session (from validation failures)
        $formErrors = $_SESSION['form_errors'] ?? [];
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['form_data']);

        $this->render('admin/users/create', [
            'user' => null,
            'is_edit' => false,
            'form_errors' => $formErrors,
            'form_data' => $formData,
            'flash' => $this->getFlash(),
            'page_title' => 'Create User',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Store new user
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/users/create');
        }

        try {
            // Get form data
            $data = [
                'username' => $this->sanitize($this->getParam('username', '', 'post')),
                'email' => $this->sanitize($this->getParam('email', '', 'post')),
                'password' => $this->getParam('password', '', 'post')
            ];
            
            // Validate user data
            $errors = UserModel::validateUserData($data);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/users/create');
            }

            // Create user using Auth utility for proper password hashing
            $userId = $this->auth->createUser($data['username'], $data['email'], $data['password']);
            
            if ($userId !== false) {
                $this->setFlash('success', 'User "' . $data['username'] . '" created successfully.');
                $this->redirect('/admin/users/' . $userId . '/edit');
            } else {
                throw new Exception('Failed to create user');
            }

        } catch (Exception $e) {
            $this->logError('User store error', $e);
            $this->setFlash('error', 'An error occurred while creating the user.');
            $this->redirect('/admin/users/create');
        }
    }

    /**
     * Show edit user form
     * 
     * @return void
     */
    public function edit(): void
    {
        $id = (int) $this->getParam('id');
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid user ID.');
            $this->redirect('/admin/users');
        }

        try {
            $user = UserModel::find($id);
            
            if (!$user) {
                $this->setFlash('error', 'User not found.');
                $this->redirect('/admin/users');
            }

            // Get form errors and data from session (from validation failures)
            $formErrors = $_SESSION['form_errors'] ?? [];
            $formData = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_errors'], $_SESSION['form_data']);

            // Get user statistics
            $stats = [
                'total_content' => $user->getContentCount(),
                'published_content' => $user->getContentCount(null, 'published'),
                'draft_content' => $user->getContentCount(null, 'draft'),
                'recent_content' => $user->getRecentContent(5)
            ];

            $this->render('admin/users/edit', [
                'user' => $user,
                'is_edit' => true,
                'form_errors' => $formErrors,
                'form_data' => $formData,
                'user_stats' => $stats,
                'is_current_user' => $id === $this->getCurrentUserId(),
                'flash' => $this->getFlash(),
                'page_title' => 'Edit User: ' . $user->getAttribute('username'),
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            $this->logError('User edit error', $e);
            $this->setFlash('error', 'An error occurred while loading user.');
            $this->redirect('/admin/users');
        }
    }

    /**
     * Update existing user
     * 
     * @return void
     */
    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
        }

        $id = (int) $this->getParam('id');
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid user ID.');
            $this->redirect('/admin/users');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/users/' . $id . '/edit');
        }

        try {
            $user = UserModel::find($id);
            
            if (!$user) {
                $this->setFlash('error', 'User not found.');
                $this->redirect('/admin/users');
            }

            // Get form data
            $data = [
                'username' => $this->sanitize($this->getParam('username', '', 'post')),
                'email' => $this->sanitize($this->getParam('email', '', 'post')),
                'password' => $this->getParam('password', '', 'post')
            ];
            
            // Validate user data (exclude current user ID for uniqueness checks)
            $errors = UserModel::validateUserData($data, $id);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/users/' . $id . '/edit');
            }

            // Update user attributes
            $user->setAttribute('username', $data['username']);
            $user->setAttribute('email', $data['email']);

            // Update password if provided
            if (!empty($data['password'])) {
                $user->updatePassword($data['password']);
            }
            
            if ($user->save()) {
                // Update session data if current user is being edited
                if ($id === $this->getCurrentUserId()) {
                    $_SESSION['username'] = $data['username'];
                    $_SESSION['email'] = $data['email'];
                }

                $this->setFlash('success', 'User "' . $data['username'] . '" updated successfully.');
                $this->redirect('/admin/users/' . $id . '/edit');
            } else {
                throw new Exception('Failed to update user');
            }

        } catch (Exception $e) {
            $this->logError('User update error', $e);
            $this->setFlash('error', 'An error occurred while updating the user.');
            $this->redirect('/admin/users/' . $id . '/edit');
        }
    }

    /**
     * Delete user with confirmation
     * 
     * @return void
     */
    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
        }

        $id = (int) $this->getParam('id');
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid user ID.');
            $this->redirect('/admin/users');
        }

        // Prevent self-deletion
        if ($id === $this->getCurrentUserId()) {
            $this->setFlash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/users');
        }

        try {
            $user = UserModel::find($id);
            
            if (!$user) {
                $this->setFlash('error', 'User not found.');
                $this->redirect('/admin/users');
            }

            $username = $user->getAttribute('username');
            
            // Check if user has content
            if ($user->hasContent()) {
                $this->setFlash('error', 'Cannot delete user "' . $username . '" because they have associated content. Please reassign or delete their content first.');
                $this->redirect('/admin/users');
            }
            
            if ($user->delete()) {
                $this->setFlash('success', 'User "' . $username . '" deleted successfully.');
            } else {
                $this->setFlash('error', 'Failed to delete user.');
            }

        } catch (Exception $e) {
            $this->logError('User delete error', $e);
            $this->setFlash('error', 'An error occurred while deleting the user.');
        }

        $this->redirect('/admin/users');
    }

    /**
     * Handle bulk operations on users
     * 
     * @return void
     */
    public function bulkAction(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $action = $this->getParam('action', '', 'post');
            $userIds = $this->getParam('user_ids', [], 'post');
            
            if (empty($action) || empty($userIds) || !is_array($userIds)) {
                $this->renderJson(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            // Convert to integers and remove current user ID
            $currentUserId = $this->getCurrentUserId();
            $userIds = array_filter(array_map('intval', $userIds), function($id) use ($currentUserId) {
                return $id > 0 && $id !== $currentUserId;
            });

            if (empty($userIds)) {
                $this->renderJson(['success' => false, 'message' => 'No valid users selected'], 400);
            }

            $processed = 0;
            $errors = [];

            switch ($action) {
                case 'delete':
                    foreach ($userIds as $userId) {
                        $user = UserModel::find($userId);
                        
                        if (!$user) {
                            $errors[] = "User ID {$userId} not found";
                            continue;
                        }

                        if ($user->hasContent()) {
                            $errors[] = "Cannot delete user \"{$user->getAttribute('username')}\" (has content)";
                            continue;
                        }

                        if ($user->delete()) {
                            $processed++;
                        } else {
                            $errors[] = "Failed to delete user \"{$user->getAttribute('username')}\"";
                        }
                    }
                    break;

                default:
                    $this->renderJson(['success' => false, 'message' => 'Invalid action'], 400);
            }

            $message = "Processed {$processed} user(s)";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            $this->renderJson([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            $this->logError('Users bulk action error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while processing bulk action'], 500);
        }
    }

    /**
     * Export users data (CSV format)
     * 
     * @return void
     */
    public function export(): void
    {
        try {
            // Get all users
            $users = UserModel::getForAdmin();

            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Open output stream
            $output = fopen('php://output', 'w');

            // Write CSV header
            fputcsv($output, [
                'ID',
                'Username',
                'Email',
                'Total Content',
                'Published Content',
                'Draft Content',
                'Created At'
            ]);

            // Write user data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['user_id'],
                    $user['username'],
                    $user['email'],
                    $user['content_count'] ?? 0,
                    $user['published_count'] ?? 0,
                    ($user['content_count'] ?? 0) - ($user['published_count'] ?? 0),
                    $user['created_at']
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            $this->logError('Users export error', $e);
            $this->setFlash('error', 'An error occurred while exporting users.');
            $this->redirect('/admin/users');
        }
    }

    /**
     * Get user statistics via AJAX
     * 
     * @return void
     */
    public function getStats(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $id = (int) $this->getParam('id');
            
            if ($id <= 0) {
                $this->renderJson(['success' => false, 'message' => 'Invalid user ID'], 400);
            }

            $user = UserModel::find($id);
            
            if (!$user) {
                $this->renderJson(['success' => false, 'message' => 'User not found'], 404);
            }

            $stats = [
                'total_content' => $user->getContentCount(),
                'published_content' => $user->getContentCount(null, 'published'),
                'draft_content' => $user->getContentCount(null, 'draft'),
                'recent_content' => $user->getRecentContent(5)
            ];

            $this->renderJson(['success' => true, 'stats' => $stats]);

        } catch (Exception $e) {
            $this->logError('User stats error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while loading stats'], 500);
        }
    }

    /**
     * Check username availability via AJAX
     * 
     * @return void
     */
    public function checkUsername(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $username = $this->sanitize($this->getParam('username', ''));
            $excludeId = (int) $this->getParam('exclude_id', 0);
            
            if (empty($username)) {
                $this->renderJson(['success' => false, 'message' => 'Username is required'], 400);
            }

            $available = UserModel::isUsernameAvailable($username, $excludeId > 0 ? $excludeId : null);

            $this->renderJson([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'Username is available' : 'Username is already taken'
            ]);

        } catch (Exception $e) {
            $this->logError('Username check error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while checking username'], 500);
        }
    }

    /**
     * Check email availability via AJAX
     * 
     * @return void
     */
    public function checkEmail(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $email = $this->sanitize($this->getParam('email', ''));
            $excludeId = (int) $this->getParam('exclude_id', 0);
            
            if (empty($email)) {
                $this->renderJson(['success' => false, 'message' => 'Email is required'], 400);
            }

            $available = UserModel::isEmailAvailable($email, $excludeId > 0 ? $excludeId : null);

            $this->renderJson([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'Email is available' : 'Email is already taken'
            ]);

        } catch (Exception $e) {
            $this->logError('Email check error', $e);
            $this->renderJson(['success' => false, 'message' => 'An error occurred while checking email'], 500);
        }
    }
}
