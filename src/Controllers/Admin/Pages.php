<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Page as PageModel;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Pages extends BaseController
{
    private const ITEMS_PER_PAGE = 20;

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
        $sortBy = $this->request->get('sort_by', 'updated_at');
        $sortDir = strtoupper($this->request->get('sort_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $filters = ['search' => $search, 'sort_by' => $sortBy, 'sort_dir' => $sortDir];

        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        $pages = PageModel::getForAdmin($filters, self::ITEMS_PER_PAGE, $offset);
        $totalCount = PageModel::countForAdmin($filters);
        $totalPages = (int) ceil($totalCount / self::ITEMS_PER_PAGE);

        $this->render('admin/pages/index', [
            'pages' => $pages,
            'filters' => $filters,
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'total_items' => $totalCount],
            'page_title' => 'Page Management',
        ]);
    }

    public function create(): void
    {
        $this->render('admin/pages/create', [
            'page_title' => 'Create Page',
        ]);
    }

    public function store(): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/pages/create');
            return;
        }

        $data = $this->getFormData();
        if (empty($data['url_alias'])) {
            $data['url_alias'] = PageModel::generateAlias($data['title']);
        }

        $errors = PageModel::validatePageData($data);
        if (!empty($errors)) {
            $this->setFlash('error', implode('; ', $errors));
            $this->redirect('/admin/pages/create');
            return;
        }

        $page = PageModel::create($data);
        $this->setFlash('success', 'Page created successfully.');
        $this->redirect('/admin/pages/' . $page->getId() . '/edit');
    }

    public function edit(string $id): void
    {
        $page = PageModel::find((int)$id);
        if (!$page) {
            $this->setFlash('error', 'Page not found.');
            $this->redirect('/admin/pages');
            return;
        }

        $this->render('admin/pages/edit', [
            'page' => $page,
            'page_title' => 'Edit Page: ' . $page->getAttribute('title'),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }

        $page = PageModel::find((int)$id);
        if (!$page) {
            $this->setFlash('error', 'Page not found.');
            $this->redirect('/admin/pages');
            return;
        }

        $data = $this->getFormData();
        if (empty($data['url_alias'])) {
            $data['url_alias'] = PageModel::generateAlias($data['title'], (int)$id);
        }

        $errors = PageModel::validatePageData($data, (int)$id);
        if (!empty($errors)) {
            $this->setFlash('error', implode('; ', $errors));
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }

        $page->setAttributes($data);
        $page->save();

        $this->setFlash('success', 'Page updated successfully.');
        $this->redirect('/admin/pages/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/pages');
            return;
        }

        $page = PageModel::find((int)$id);
        if ($page) {
            $page->delete();
            $this->setFlash('success', 'Page deleted successfully.');
        }
        
        $this->redirect('/admin/pages');
    }

    private function getFormData(): array
    {
        return [
            'title' => $this->request->post('title', ''),
            'body' => $this->request->post('body', ''),
            'url_alias' => $this->request->post('url_alias', ''),
            'meta_description' => $this->request->post('meta_description', ''),
            'meta_keywords' => $this->request->post('meta_keywords', '')
        ];
    }
}