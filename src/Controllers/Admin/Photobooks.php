<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Photobooks extends BaseController
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
        $page = $this->request->get('page', 1, 'int');
        $search = $this->request->get('search', '');
        $status = $this->request->get('status', '');
        $sortBy = $this->request->get('sort_by', 'created_at');
        $sortDir = $this->request->get('sort_dir', 'DESC');

        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => ContentModel::TYPE_PHOTOBOOK,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];

        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;

        $photobooks = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);

        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
        ];

        $this->render('admin/photobooks/index', [
            'photobooks' => $photobooks,
            'filters' => $filters,
            'pagination' => $pagination,
            'page_title' => 'Photobooks Management',
        ]);
    }

    public function reorder(): void
    {
        $photobooks = ContentModel::getForReordering(ContentModel::TYPE_PHOTOBOOK);

        $this->render('admin/photobooks/reorder', [
            'photobooks' => $photobooks,
            'page_title' => 'Reorder Photobooks',
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

            if (ContentModel::updateSortOrder($orderData, ContentModel::TYPE_PHOTOBOOK)) {
                $this->renderJson(['success' => true, 'message' => 'Photobook order updated successfully']);
            } else {
                throw new Exception('Failed to update photobook order');
            }
        } catch (Exception $e) {
            $this->logError('Update photobook order error', $e);
            $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
