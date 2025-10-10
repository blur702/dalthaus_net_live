<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\User as UserModel;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Users extends BaseController
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
        $sortBy = $this->request->get('sort_by', 'created_at');
        $sortDir = strtoupper($this->request->get('sort_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $filters = ['search' => $search, 'sort_by' => $sortBy, 'sort_dir' => $sortDir];

        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        $users = UserModel::getForAdmin($filters, self::ITEMS_PER_PAGE, $offset);
        $totalCount = UserModel::countForAdmin($filters);
        $totalPages = (int) ceil($totalCount / self::ITEMS_PER_PAGE);

        $this->render('admin/users/index', [
            'users' => $users,
            'filters' => $filters,
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'total_items' => $totalCount],
            'current_user_id' => $this->auth->id(),
            'page_title' => 'User Management',
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/create', [
            'page_title' => 'Create User',
        ]);
    }

    public function store(): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/users/create');
            return;
        }

        $data = [
            'username' => $this->request->post('username', ''),
            'display_name' => $this->request->post('display_name', ''),
            'email' => $this->request->post('email', ''),
            'password' => $this->request->post('password', '')
        ];

        $errors = UserModel::validateUserData($data);
        if (!empty($errors)) {
            $this->setFlash('error', implode('; ', $errors));
            $this->redirect('/admin/users/create');
            return;
        }

        $userId = $this->auth->createUser($data['username'], $data['email'], $data['password']);
        if ($userId) {
            $user = UserModel::find($userId);
            if ($user) {
                $user->setAttribute('display_name', $data['display_name']);
                $user->save();
            }
            $this->setFlash('success', 'User created successfully.');
            $this->redirect('/admin/users/' . $userId . '/edit');
        } else {
            $this->setFlash('error', 'Failed to create user.');
            $this->redirect('/admin/users/create');
        }
    }

    public function edit(string $id): void
    {
        $user = UserModel::find((int)$id);
        if (!$user) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('/admin/users');
            return;
        }

        $this->render('admin/users/edit', [
            'user' => $user,
            'user_stats' => ['total_content' => $user->getContentCount(), 'published_content' => $user->getContentCount(null, 'published'), 'draft_content' => $user->getContentCount(null, 'draft'), 'recent_content' => $user->getRecentContent(5)],
            'is_current_user' => (int)$id === $this->auth->id(),
            'page_title' => 'Edit User: ' . $user->getAttribute('username'),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $user = UserModel::find((int)$id);
        if (!$user) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('/admin/users');
            return;
        }

        $data = [
            'username' => $this->request->post('username', ''),
            'display_name' => $this->request->post('display_name', ''),
            'email' => $this->request->post('email', ''),
            'password' => $this->request->post('password', '')
        ];

        $errors = UserModel::validateUserData($data, (int)$id);
        if (!empty($errors)) {
            $this->setFlash('error', implode('; ', $errors));
            $this->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $user->setAttribute('username', $data['username']);
        $user->setAttribute('display_name', $data['display_name']);
        $user->setAttribute('email', $data['email']);
        if (!empty($data['password'])) {
            $user->updatePassword($data['password']);
        }

        if ($user->save()) {
            $this->setFlash('success', 'User updated successfully.');
        } else {
            $this->setFlash('error', 'Failed to update user.');
        }
        $this->redirect('/admin/users/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/users');
            return;
        }

        $id = (int)$id;
        if ($id === $this->auth->id()) {
            $this->setFlash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
            return;
        }

        $user = UserModel::find($id);
        if ($user) {
            if ($user->hasContent()) {
                $this->setFlash('error', 'Cannot delete user with content.');
            } else {
                $user->delete();
                $this->setFlash('success', 'User deleted successfully.');
            }
        }
        
        $this->redirect('/admin/users');
    }
}