<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Articles extends BaseController
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
        $page = (int) $this->request->get('page', 1);
        $search = $this->request->get('search', '');
        $status = $this->request->get('status', '');
        $sortBy = $this->request->get('sort_by', 'created_at');
        $sortDir = $this->request->get('sort_dir', 'DESC');

        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => ContentModel::TYPE_ARTICLE,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];

        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;

        $articles = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);

        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
        ];

        $this->render('admin/articles/index', [
            'articles' => $articles,
            'filters' => $filters,
            'pagination' => $pagination,
            'page_title' => 'Articles Management',
        ]);
    }

    public function reorder(): void
    {
        $articles = ContentModel::getForReordering(ContentModel::TYPE_ARTICLE);

        $this->render('admin/articles/reorder', [
            'articles' => $articles,
            'page_title' => 'Reorder Articles',
        ]);
    }

    public function updateOrder(): void
    {
        if (!$this->request->isPost()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->renderJson(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            return;
        }

        try {
            $orderJson = $this->request->post('order', '');
            if (empty($orderJson)) {
                throw new Exception('No order data provided');
            }

            $orderData = json_decode($orderJson, true);
            if (!is_array($orderData)) {
                throw new Exception('Invalid order data format');
            }

            if (ContentModel::updateSortOrder($orderData, ContentModel::TYPE_ARTICLE)) {
                $this->renderJson(['success' => true, 'message' => 'Article order updated successfully']);
            } else {
                throw new Exception('Failed to update article order');
            }
        } catch (Exception $e) {
            $this->logError('Update article order error', $e);
            $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
