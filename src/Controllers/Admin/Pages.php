<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Page as PageModel;
use Exception;

/**
 * Admin Pages Controller
 * 
 * Handles CRUD operations for static pages with TinyMCE integration,
 * URL alias management, and SEO meta tag handling.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Pages extends BaseController
{
    /**
     * Items per page for pagination
     */
    private const ITEMS_PER_PAGE = 20;

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
     * List all static pages with search functionality
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Get filter parameters
            $page = max(1, (int) $this->getParam('page', 1));
            $search = $this->sanitize($this->getParam('search', ''));
            $sortBy = $this->getParam('sort_by', 'updated_at');
            $sortDir = strtoupper($this->getParam('sort_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            // Build filters array
            $filters = array_filter([
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir
            ]);

            // Calculate pagination
            $offset = ($page - 1) * self::ITEMS_PER_PAGE;
            
            // Get pages and total count
            $pages = PageModel::getForAdmin($filters, self::ITEMS_PER_PAGE, $offset);
            $totalCount = PageModel::countForAdmin($filters);
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

            $this->render('admin/pages/index', [
                'pages' => $pages,
                'filters' => $filters,
                'pagination' => $pagination,
                'flash' => $this->getFlash(),
                'page_title' => 'Page Management',
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            error_log('Pages index error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while loading pages.');
            $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Show create page form
     * 
     * @return void
     */
    public function create(): void
    {
        // Get form errors and data from session (from validation failures)
        $formErrors = $_SESSION['form_errors'] ?? [];
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['form_data']);

        $this->render('admin/pages/create', [
            'page' => null,
            'is_edit' => false,
            'form_errors' => $formErrors,
            'form_data' => $formData,
            'flash' => $this->getFlash(),
            'page_title' => 'Create Page',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Store new page
     * 
     * @return void
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/pages');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/pages/create');
        }

        try {
            // Get form data
            $data = $this->getFormData();
            
            // Generate URL alias if not provided
            if (empty($data['url_alias']) && !empty($data['title'])) {
                $data['url_alias'] = PageModel::generateAlias($data['title']);
            }
            
            // Validate page data
            $errors = PageModel::validatePageData($data);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/pages/create');
            }

            // Set timestamps
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Create page
            $page = PageModel::create($data);
            
            if ($page) {
                $this->setFlash('success', 'Page "' . $data['title'] . '" created successfully.');
                $this->redirect('/admin/pages/' . $page->getId() . '/edit');
            } else {
                throw new Exception('Failed to create page');
            }

        } catch (Exception $e) {
            error_log('Page store error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while creating the page.');
            $this->redirect('/admin/pages/create');
        }
    }

    /**
     * Show edit page form
     * 
     * @return void
     */
    public function edit(string $id = ''): void
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid page ID.');
            $this->redirect('/admin/pages');
        }

        try {
            $page = PageModel::find($id);
            
            if (!$page) {
                $this->setFlash('error', 'Page not found.');
                $this->redirect('/admin/pages');
            }

            // Get form errors and data from session (from validation failures)
            $formErrors = $_SESSION['form_errors'] ?? [];
            $formData = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_errors'], $_SESSION['form_data']);

            $this->render('admin/pages/edit', [
                'page' => $page,
                'is_edit' => true,
                'form_errors' => $formErrors,
                'form_data' => $formData,
                'flash' => $this->getFlash(),
                'page_title' => 'Edit Page: ' . $page->getAttribute('title'),
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            error_log('Page edit error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while loading the page.');
            $this->redirect('/admin/pages');
        }
    }

    /**
     * Update existing page
     * 
     * @return void
     */
    public function update(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/pages');
        }

        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid page ID.');
            $this->redirect('/admin/pages');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/pages/' . $id . '/edit');
        }

        try {
            $page = PageModel::find($id);
            
            if (!$page) {
                $this->setFlash('error', 'Page not found.');
                $this->redirect('/admin/pages');
            }

            // Get form data
            $data = $this->getFormData();
            
            // Generate URL alias if not provided or changed
            if (empty($data['url_alias']) && !empty($data['title'])) {
                $data['url_alias'] = PageModel::generateAlias($data['title'], $id);
            } elseif (!empty($data['url_alias']) && $data['url_alias'] !== $page->getAttribute('url_alias')) {
                // Validate new alias
                if (!PageModel::isAliasAvailable($data['url_alias'], $id)) {
                    $data['url_alias'] = PageModel::generateAlias($data['title'], $id);
                }
            }
            
            // Validate page data
            $errors = PageModel::validatePageData($data, $id);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/pages/' . $id . '/edit');
            }

            // Set updated timestamp
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Update page attributes
            foreach ($data as $key => $value) {
                $page->setAttribute($key, $value);
            }
            
            if ($page->save()) {
                $this->setFlash('success', 'Page "' . $data['title'] . '" updated successfully.');
                $this->redirect('/admin/pages/' . $id . '/edit');
            } else {
                throw new Exception('Failed to update page');
            }

        } catch (Exception $e) {
            error_log('Page update error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while updating the page.');
            $this->redirect('/admin/pages/' . $id . '/edit');
        }
    }

    /**
     * Delete page with confirmation
     * 
     * @return void
     */
    public function delete(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/pages');
        }

        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid page ID.');
            $this->redirect('/admin/pages');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/pages');
        }

        try {
            $page = PageModel::find($id);
            
            if (!$page) {
                $this->setFlash('error', 'Page not found.');
                $this->redirect('/admin/pages');
            }

            $title = $page->getAttribute('title');
            
            if ($page->delete()) {
                $this->setFlash('success', 'Page "' . $title . '" deleted successfully.');
            } else {
                $this->setFlash('error', 'Failed to delete page.');
            }

        } catch (Exception $e) {
            error_log('Page delete error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while deleting the page.');
        }

        $this->redirect('/admin/pages');
    }

    /**
     * AJAX endpoint to generate URL alias from title
     * 
     * @return void
     */
    public function generateAlias(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $title = $this->sanitize($this->getParam('title', '', 'post'));
            $pageId = (int) $this->getParam('page_id', 0, 'post');
            
            if (empty($title)) {
                $this->renderJson(['success' => false, 'message' => 'Title is required'], 400);
            }

            $alias = PageModel::generateAlias($title, $pageId > 0 ? $pageId : null);

            $this->renderJson([
                'success' => true,
                'alias' => $alias
            ]);

        } catch (Exception $e) {
            error_log('Generate alias error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while generating alias'], 500);
        }
    }

    /**
     * AJAX endpoint to check URL alias availability
     * 
     * @return void
     */
    public function checkAlias(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $alias = $this->sanitize($this->getParam('alias', ''));
            $pageId = (int) $this->getParam('page_id', 0);
            
            if (empty($alias)) {
                $this->renderJson(['success' => false, 'message' => 'Alias is required'], 400);
            }

            $available = PageModel::isAliasAvailable($alias, $pageId > 0 ? $pageId : null);

            $this->renderJson([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'URL alias is available' : 'URL alias is already taken'
            ]);

        } catch (Exception $e) {
            error_log('Check alias error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while checking alias'], 500);
        }
    }

    /**
     * AJAX autosave endpoint for pages
     * 
     * @return void
     */
    public function autosave(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $id = (int) $this->getParam('id', 0, 'post');
            $title = $this->sanitize($this->getParam('title', '', 'post'));
            $body = $this->getParam('body', '', 'post');
            
            if ($id <= 0) {
                $this->renderJson(['success' => false, 'message' => 'Invalid page ID'], 400);
            }

            $page = PageModel::find($id);
            
            if (!$page) {
                $this->renderJson(['success' => false, 'message' => 'Page not found'], 404);
            }

            // Update only title and body for autosave
            $page->setAttribute('title', $title);
            $page->setAttribute('body', $body);
            $page->setAttribute('updated_at', date('Y-m-d H:i:s'));
            
            if ($page->save()) {
                $this->renderJson([
                    'success' => true, 
                    'message' => 'Draft saved automatically',
                    'timestamp' => date('g:i A')
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to save draft'], 500);
            }

        } catch (Exception $e) {
            error_log('Page autosave error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while saving'], 500);
        }
    }

    /**
     * Image upload endpoint for TinyMCE in pages
     * 
     * @return void
     */
    public function uploadImage(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['error' => 'Invalid request'], 400);
        }

        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->renderJson(['error' => 'No file uploaded or upload error'], 400);
            }

            $file = $_FILES['file'];
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $uploadDir = __DIR__ . '/../../../uploads/pages';
            
            // Create upload directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = $this->handleUpload($file, $uploadDir, $allowedTypes);
            
            if ($filename === false) {
                $this->renderJson(['error' => 'Failed to upload image'], 400);
            }

            $this->renderJson([
                'location' => '/uploads/pages/' . $filename
            ]);

        } catch (Exception $e) {
            error_log('Page image upload error: ' . $e->getMessage());
            $this->renderJson(['error' => 'An error occurred while uploading image'], 500);
        }
    }

    /**
     * Get pages for AJAX dropdown (used in other forms)
     * 
     * @return void
     */
    public function getPages(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $search = $this->sanitize($this->getParam('search', ''));
            
            $pages = [];
            if (!empty($search)) {
                // Search pages by title
                $results = $this->db->fetchAll(
                    'SELECT page_id, title, url_alias FROM pages WHERE title LIKE ? ORDER BY title LIMIT 20',
                    ['%' . $search . '%']
                );
            } else {
                // Get recent pages
                $results = PageModel::getRecent(20);
            }

            foreach ($results as $page) {
                $pages[] = [
                    'id' => $page['page_id'],
                    'title' => $page['title'],
                    'url_alias' => $page['url_alias'],
                    'url' => '/page/' . $page['url_alias']
                ];
            }

            $this->renderJson([
                'success' => true,
                'pages' => $pages
            ]);

        } catch (Exception $e) {
            error_log('Get pages error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while loading pages'], 500);
        }
    }

    /**
     * Preview page content
     * 
     * @return void
     */
    public function preview(string $id = ''): void
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid page ID.');
            $this->redirect('/admin/pages');
        }

        try {
            $page = PageModel::find($id);
            
            if (!$page) {
                $this->setFlash('error', 'Page not found.');
                $this->redirect('/admin/pages');
            }

            // Use public layout for preview
            $this->view->layout('default');

            $this->render('public/pages/show', [
                'page' => $page,
                'page_title' => $page->getAttribute('title'),
                'meta_tags' => $page->getMetaTags(),
                'is_preview' => true
            ]);

        } catch (Exception $e) {
            error_log('Page preview error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while previewing the page.');
            $this->redirect('/admin/pages');
        }
    }

    /**
     * Bulk delete pages
     * 
     * @return void
     */
    public function bulkDelete(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $pageIds = $this->getParam('page_ids', [], 'post');
            
            if (empty($pageIds) || !is_array($pageIds)) {
                $this->renderJson(['success' => false, 'message' => 'No pages selected'], 400);
            }

            // Convert to integers
            $pageIds = array_filter(array_map('intval', $pageIds), function($id) {
                return $id > 0;
            });

            if (empty($pageIds)) {
                $this->renderJson(['success' => false, 'message' => 'No valid pages selected'], 400);
            }

            $deleted = 0;
            $errors = [];

            foreach ($pageIds as $pageId) {
                $page = PageModel::find($pageId);
                
                if (!$page) {
                    $errors[] = "Page ID {$pageId} not found";
                    continue;
                }

                if ($page->delete()) {
                    $deleted++;
                } else {
                    $errors[] = "Failed to delete page \"{$page->getAttribute('title')}\"";
                }
            }

            $message = "Deleted {$deleted} page(s)";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            $this->renderJson([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            error_log('Pages bulk delete error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while deleting pages'], 500);
        }
    }

    /**
     * Get form data from POST request
     * 
     * @return array
     */
    private function getFormData(): array
    {
        return [
            'title' => $this->sanitize($this->getParam('title', '', 'post')),
            'body' => $this->getParam('body', '', 'post'), // Don't sanitize HTML content
            'url_alias' => $this->sanitize($this->getParam('url_alias', '', 'post')),
            'meta_description' => $this->sanitize($this->getParam('meta_description', '', 'post')),
            'meta_keywords' => $this->sanitize($this->getParam('meta_keywords', '', 'post'))
        ];
    }
}
