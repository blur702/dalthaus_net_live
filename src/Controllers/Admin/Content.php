<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use App\Models\Autosave;
use CMS\Utils\FileUpload;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Content extends BaseController
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
        $type = $this->request->get('type', '');
        $search = $this->request->get('search', '');
        $status = $this->request->get('status', '');
        $sortBy = $this->request->get('sort_by', 'created_at');
        $sortDir = $this->request->get('sort_dir', 'DESC');
        
        $validTypes = [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK];
        if (!empty($type) && !in_array($type, $validTypes)) {
            $type = '';
        }
        
        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => $type,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
        
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        
        $content = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
        ];
        
        $this->render('admin/content/index', [
            'content' => $content,
            'filters' => $filters,
            'pagination' => $pagination,
            'page_title' => 'Content Management',
        ]);
    }

    public function create(): void
    {
        $type = $this->request->get('type', ContentModel::TYPE_ARTICLE);
        if (!in_array($type, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
            $type = ContentModel::TYPE_ARTICLE;
        }

        $this->render('admin/content/create', [
            'content_type' => $type,
            'page_title' => 'Create ' . ucfirst($type),
        ]);
    }

    public function store(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content/create');
            return;
        }

        try {
            $data = $this->getFormData();
            $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias']);

            $errors = $this->validateContentData($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('; ', $errors));
                $this->redirect('/admin/content/create?type=' . urlencode($data['content_type']));
                return;
            }

            $data['user_id'] = $this->auth->id();
            $data['sort_order'] = ContentModel::getNextSortOrder();

            $content = ContentModel::create($data);

            $this->setFlash('success', ucfirst($data['content_type']) . ' created successfully.');
            $this->redirect('/admin/content/' . $content->getId() . '/edit');

        } catch (Exception $e) {
            $this->logError('Content store error', $e);
            $this->setFlash('error', 'An error occurred while creating content.');
            $this->redirect('/admin/content/create');
        }
    }

    private function getFormData(): array
    {
        $status = $this->request->post('action') === 'publish' ? ContentModel::STATUS_PUBLISHED : ContentModel::STATUS_DRAFT;
        
        return [
            'title' => $this->request->post('title', ''),
            'teaser' => $this->request->post('teaser', ''),
            'body' => $this->request->post('body', ''),
            'url_alias' => $this->request->post('url_alias', ''),
            'content_type' => $this->request->post('content_type', ContentModel::TYPE_ARTICLE),
            'status' => $status,
            'published_at' => $this->request->post('published_at', null)
        ];
    }

    private function ensureUniqueUrlAlias(string $urlAlias, ?int $excludeId = null): string
    {
        $baseAlias = $urlAlias;
        $counter = 2;
        while ($existing = ContentModel::findByUrlAlias($urlAlias)) {
            if ($excludeId && $existing->getId() === $excludeId) {
                break;
            }
            $urlAlias = $baseAlias . '-' . $counter++;
        }
        return $urlAlias;
    }

    private function validateContentData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }
        if (empty($data['url_alias'])) {
            $errors['url_alias'] = 'URL alias is required';
        }
        return $errors;
    }

    public function edit(string $id): void
    {
        $content = ContentModel::find((int)$id);
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        $this->render('admin/content/edit', [
            'content' => $content,
            'page_title' => 'Edit ' . ucfirst($content->getAttribute('content_type')),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content/' . $id . '/edit');
            return;
        }

        $content = ContentModel::find((int)$id);
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        try {
            $data = $this->getFormData();
            $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias'], (int)$id);

            $errors = $this->validateContentData($data, (int)$id);
            if (!empty($errors)) {
                $this->setFlash('error', implode('; ', $errors));
                $this->redirect('/admin/content/' . $id . '/edit');
                return;
            }

            $content->setAttributes($data);
            $content->save();

            $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' updated successfully.');
            $this->redirect('/admin/content/' . $id . '/edit');

        } catch (Exception $e) {
            $this->logError('Content update error', $e);
            $this->setFlash('error', 'An error occurred while updating content.');
            $this->redirect('/admin/content/' . $id . '/edit');
        }
    }

    public function delete(string $id): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content');
            return;
        }

        $content = ContentModel::find((int)$id);
        if ($content) {
            $content->delete();
            $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' deleted successfully.');
        }
        
        $this->redirect('/admin/content');
    }
}