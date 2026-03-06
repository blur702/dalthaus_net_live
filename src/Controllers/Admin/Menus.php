<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Menu;
use CMS\Models\MenuItem;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Menus extends BaseController
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
        $this->render('admin/menus/index', [
            'menus' => Menu::getAllWithCounts(),
            'page_title' => 'Menu Management',
        ]);
    }

    public function edit(string $id): void
    {
        $menu = Menu::find((int)$id);
        if (!$menu) {
            $this->setFlash('error', 'Menu not found.');
            $this->redirect('/admin/menus');
            return;
        }

        $this->render('admin/menus/edit', [
            'menu' => $menu,
            'menu_items' => MenuItem::getByMenuId($menu->getId(), true),
            'available_content' => MenuItem::getAvailableContent(),
            'parent_options' => MenuItem::getParentOptions($menu->getId()),
            'page_title' => 'Edit Menu: ' . $menu->getAttribute('menu_name'),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/menus/' . $id);
            return;
        }

        $menu = Menu::find((int)$id);
        if (!$menu) {
            $this->setFlash('error', 'Menu not found.');
            $this->redirect('/admin/menus');
            return;
        }

        $menu->setAttribute('menu_name', $this->request->post('menu_name'));
        if ($menu->save()) {
            $this->setFlash('success', 'Menu updated successfully.');
        } else {
            $this->setFlash('error', 'Failed to update menu.');
        }
        $this->redirect('/admin/menus/' . $id);
    }

    public function addItem(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/menus/' . $id);
            return;
        }

        $data = [
            'menu_id' => (int)$id,
            'label' => $this->request->post('label'),
            'link' => $this->request->post('link'),
            'parent_id' => $this->request->post('parent_id', null, 'int'),
            'target' => $this->request->post('target', '_self'),
            'css_class' => $this->request->post('css_class', '')
        ];

        MenuItem::createItem($data);
        $this->setFlash('success', 'Menu item added.');
        $this->redirect('/admin/menus/' . $id);
    }

    public function deleteItem(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/menus');
            return;
        }

        $item = MenuItem::find((int)$id);
        if ($item) {
            $menuId = $item->getAttribute('menu_id');
            $item->delete();
            $this->setFlash('success', 'Menu item deleted.');
            $this->redirect('/admin/menus/' . $menuId);
        } else {
            $this->setFlash('error', 'Menu item not found.');
            $this->redirect('/admin/menus');
        }
    }

    public function reorderItems(): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $hierarchyData = $this->request->json();
        if (MenuItem::updateHierarchy($hierarchyData)) {
            $this->renderJson(['success' => true, 'message' => 'Menu items reordered.']);
        } else {
            $this->renderJson(['success' => false, 'message' => 'Failed to reorder items.'], 500);
        }
    }
}