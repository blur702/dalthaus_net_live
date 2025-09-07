<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Menu;
use CMS\Models\MenuItem;
use Exception;

/**
 * Admin Menus Controller
 * 
 * Handles menu and menu item management with hierarchical structure,
 * drag-and-drop reordering, and comprehensive CRUD operations.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Menus extends BaseController
{
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
     * List all menus with item counts
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            $menus = Menu::getAllWithCounts();

            $this->render('admin/menus/index', [
                'menus' => $menus,
                'flash' => $this->getFlash(),
                'page_title' => 'Menu Management',
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            error_log('Menus index error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while loading menus.');
            $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Show menu editor with all menu items
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function edit(string $id = ''): void
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        try {
            $menuData = Menu::getWithItems($id);
            
            if (!$menuData) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            // Get available content for linking
            $availableContent = MenuItem::getAvailableContent();
            
            // Get parent options for menu items
            $parentOptions = MenuItem::getParentOptions($id);

            // Get form errors and data from session (from validation failures)
            $formErrors = $_SESSION['form_errors'] ?? [];
            $formData = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_errors'], $_SESSION['form_data']);

            $this->render('admin/menus/edit', [
                'menu' => $menuData['menu'] ?? [],
                'menu_items' => $menuData['items'] ?? [],
                'available_content' => $availableContent,
                'parent_options' => $parentOptions,
                'form_errors' => $formErrors,
                'form_data' => $formData,
                'flash' => $this->getFlash(),
                'page_title' => 'Edit Menu: ' . ($menuData['menu']['menu_name'] ?? 'Unknown'),
                'csrf_token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            error_log('Menu edit error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while loading the menu.');
            $this->redirect('/admin/menus');
        }
    }

    /**
     * Update menu properties
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function update(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus/' . $id);
        }

        try {
            $menu = Menu::find($id);
            
            if (!$menu) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            // Get form data
            $data = [
                'menu_name' => $this->sanitize($this->getParam('menu_name', '', 'post'))
            ];
            
            // Validate menu data
            $errors = Menu::validateMenuData($data, $id);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/menus/' . $id);
            }

            // Update menu
            if ($menu !== null) {
                $menu->setAttribute('menu_name', $data['menu_name']);
                
                if ($menu->save()) {
                    $this->setFlash('success', 'Menu "' . $data['menu_name'] . '" updated successfully.');
                    $this->redirect('/admin/menus/' . $id);
                } else {
                    throw new Exception('Failed to update menu');
                }
            } else {
                throw new Exception('Menu not found');
            }

        } catch (Exception $e) {
            error_log('Menu update error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while updating the menu.');
            $this->redirect('/admin/menus/' . $id);
        }
    }

    /**
     * Create new menu
     * 
     * @return void
     */
    public function create(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus');
        }

        try {
            // Get form data
            $data = [
                'menu_name' => $this->sanitize($this->getParam('menu_name', '', 'post'))
            ];
            
            // Validate menu data
            $errors = Menu::validateMenuData($data);
            
            if (!empty($errors)) {
                $this->setFlash('error', 'Invalid menu name: ' . implode(', ', $errors));
                $this->redirect('/admin/menus');
            }

            // Create menu
            $menu = Menu::create($data);
            
            if ($menu) {
                $this->setFlash('success', 'Menu "' . $data['menu_name'] . '" created successfully.');
                $this->redirect('/admin/menus/' . $menu->getAttribute('menu_id'));
            } else {
                throw new Exception('Failed to create menu');
            }

        } catch (Exception $e) {
            error_log('Menu create error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while creating the menu.');
            $this->redirect('/admin/menus');
        }
    }

    /**
     * Delete menu and all its items
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function delete(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus');
        }

        try {
            $menu = Menu::find($id);
            
            if (!$menu) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            $menuName = $menu ? $menu->getAttribute('menu_name') : 'Unknown';
            
            if ($menu && $menu->delete()) {
                $this->setFlash('success', 'Menu "' . $menuName . '" and all its items deleted successfully.');
            } else {
                $this->setFlash('error', 'Failed to delete menu.');
            }

        } catch (Exception $e) {
            error_log('Menu delete error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while deleting the menu.');
        }

        $this->redirect('/admin/menus');
    }

    /**
     * Duplicate menu with all items
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function duplicate(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus');
        }

        try {
            $menu = Menu::find($id);
            
            if (!$menu) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            $newName = $this->sanitize($this->getParam('new_name', '', 'post'));
            
            if (empty($newName)) {
                $this->setFlash('error', 'New menu name is required.');
                $this->redirect('/admin/menus');
            }

            // Validate new menu name
            $errors = Menu::validateMenuData(['menu_name' => $newName]);
            
            if (!empty($errors)) {
                $this->setFlash('error', 'Invalid new menu name: ' . implode(', ', $errors));
                $this->redirect('/admin/menus');
            }

            $duplicatedMenu = $menu ? $menu->duplicate($newName) : null;
            
            if ($duplicatedMenu) {
                $this->setFlash('success', 'Menu duplicated successfully as "' . $newName . '".');
                $this->redirect('/admin/menus/' . $duplicatedMenu->getAttribute('menu_id'));
            } else {
                throw new Exception('Failed to duplicate menu');
            }

        } catch (Exception $e) {
            error_log('Menu duplicate error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while duplicating the menu.');
            $this->redirect('/admin/menus');
        }
    }

    /**
     * Add new menu item
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function addItem(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $menuId = (int) $id;
        
        if ($menuId <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus/' . $menuId);
        }

        try {
            // Verify menu exists
            $menu = Menu::find($menuId);
            if (!$menu) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            // Get form data
            $data = [
                'menu_id' => $menuId,
                'label' => $this->sanitize($this->getParam('label', '', 'post')),
                'link' => $this->sanitize($this->getParam('link', '', 'post')),
                'parent_id' => $this->getParam('parent_id', null, 'post') ?: null,
                'target' => $this->getParam('target', '_self', 'post'),
                'css_class' => $this->sanitize($this->getParam('css_class', '', 'post'))
            ];

            // Validate menu item data
            $errors = MenuItem::validateItemData($data);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->setFlash('error', 'Please fix the validation errors below.');
                $this->redirect('/admin/menus/' . $menuId);
            }

            // Create menu item
            $menuItem = MenuItem::createItem($data);
            
            if ($menuItem) {
                $this->setFlash('success', 'Menu item "' . $data['label'] . '" added successfully.');
            } else {
                throw new Exception('Failed to create menu item');
            }

        } catch (Exception $e) {
            error_log('Menu item add error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while adding the menu item.');
        }

        $this->redirect('/admin/menus/' . $menuId);
    }

    /**
     * Update menu item
     * 
     * @return void
     */
    public function updateItem(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $itemId = (int) $this->getParam('item_id', 0, 'post');
        
        if ($itemId <= 0) {
            $this->renderJson(['success' => false, 'message' => 'Invalid item ID'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $menuItem = MenuItem::find($itemId);
            
            if (!$menuItem) {
                $this->renderJson(['success' => false, 'message' => 'Menu item not found'], 404);
            }

            // Get form data
            $data = [
                'label' => $this->sanitize($this->getParam('label', '', 'post')),
                'link' => $this->sanitize($this->getParam('link', '', 'post')),
                'parent_id' => $this->getParam('parent_id', null, 'post') ?: null,
                'target' => $this->getParam('target', '_self', 'post'),
                'css_class' => $this->sanitize($this->getParam('css_class', '', 'post'))
            ];

            if ($menuItem === null) {
                $this->renderJson(['success' => false, 'message' => 'Menu item not found'], 404);
                return;
            }

            // Validate menu item data
            $errors = MenuItem::validateItemData(array_merge($data, ['menu_id' => $menuItem->getAttribute('menu_id')]));
            
            if (!empty($errors)) {
                $this->renderJson(['success' => false, 'message' => 'Validation errors: ' . implode(', ', $errors)], 400);
                return;
            }

            // Update menu item
            foreach ($data as $key => $value) {
                $menuItem->setAttribute($key, $value);
            }
            
            if ($menuItem->save()) {
                $this->renderJson([
                    'success' => true, 
                    'message' => 'Menu item updated successfully',
                    'item' => $menuItem->toArray()
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to update menu item'], 500);
            }

        } catch (Exception $e) {
            error_log('Menu item update error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while updating the menu item'], 500);
        }
    }

    /**
     * Delete menu item and all its children
     * 
     * @param string $id Item ID from route
     * @return void
     */
    public function deleteItem(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/menus');
        }

        $itemId = (int) $id;
        
        if ($itemId <= 0) {
            $this->setFlash('error', 'Invalid menu item ID.');
            $this->redirect('/admin/menus');
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/menus');
        }

        try {
            $menuItem = MenuItem::find($itemId);
            
            if (!$menuItem) {
                $this->setFlash('error', 'Menu item not found.');
                $this->redirect('/admin/menus');
            }

            $menuId = $menuItem ? $menuItem->getAttribute('menu_id') : null;
            $label = $menuItem ? $menuItem->getAttribute('label') : 'Unknown';
            
            if ($menuItem && $menuItem->delete()) {
                $this->setFlash('success', 'Menu item "' . $label . '" and all its sub-items deleted successfully.');
            } else {
                $this->setFlash('error', 'Failed to delete menu item.');
            }

        } catch (Exception $e) {
            error_log('Menu item delete error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while deleting the menu item.');
        }

        $menuId = $menuId ?? $this->getParam('menu_id');
        $this->redirect('/admin/menus/' . $menuId);
    }

    /**
     * Reorder menu items via drag-and-drop
     * 
     * @return void
     */
    public function reorderItems(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed'], 403);
        }

        try {
            $hierarchyData = $this->getParam('hierarchy', [], 'post');
            
            if (empty($hierarchyData) || !is_array($hierarchyData)) {
                $this->renderJson(['success' => false, 'message' => 'Invalid hierarchy data'], 400);
            }

            // Process hierarchy data for database update
            $processedData = [];
            $this->processHierarchyData($hierarchyData, $processedData);

            if (MenuItem::updateHierarchy($processedData)) {
                $this->renderJson([
                    'success' => true, 
                    'message' => 'Menu items reordered successfully',
                    'processed' => count($processedData)
                ]);
            } else {
                $this->renderJson(['success' => false, 'message' => 'Failed to update menu order'], 500);
            }

        } catch (Exception $e) {
            error_log('Menu reorder error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while reordering menu items'], 500);
        }
    }

    /**
     * Get menu items in JSON format for AJAX requests
     * 
     * @return void
     */
    public function getItems(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $menuId = (int) $this->getParam('menu_id');
        
        if ($menuId <= 0) {
            $this->renderJson(['success' => false, 'message' => 'Invalid menu ID'], 400);
        }

        try {
            $items = MenuItem::getByMenuId($menuId, true);

            $this->renderJson([
                'success' => true,
                'items' => $items
            ]);

        } catch (Exception $e) {
            error_log('Get menu items error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while loading menu items'], 500);
        }
    }

    /**
     * Get available content for menu item linking
     * 
     * @return void
     */
    public function getAvailableContent(): void
    {
        if (!$this->isAjax()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request'], 400);
        }

        try {
            $availableContent = MenuItem::getAvailableContent();

            $this->renderJson([
                'success' => true,
                'content' => $availableContent
            ]);

        } catch (Exception $e) {
            error_log('Get available content error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while loading content'], 500);
        }
    }

    /**
     * Export menu structure as JSON
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function export(string $id = ''): void
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        try {
            $menuData = Menu::getWithItems($id);
            
            if (!$menuData) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            $menuName = $menuData['menu']['menu_name'] ?? 'Unknown';
            
            // Set headers for JSON download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="menu_' . $menuName . '_export_' . date('Y-m-d_H-i-s') . '.json"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo json_encode($menuData, JSON_PRETTY_PRINT);
            exit;

        } catch (Exception $e) {
            error_log('Menu export error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while exporting menu.');
            $this->redirect('/admin/menus');
        }
    }

    /**
     * Preview menu structure
     * 
     * @param string $id Menu ID from route
     * @return void
     */
    public function preview(string $id = ''): void
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid menu ID.');
            $this->redirect('/admin/menus');
        }

        try {
            $menuData = Menu::getWithItems($id);
            
            if (!$menuData) {
                $this->setFlash('error', 'Menu not found.');
                $this->redirect('/admin/menus');
            }

            $this->render('admin/menus/preview', [
                'menu' => $menuData['menu'] ?? [],
                'menu_items' => $menuData['items'] ?? [],
                'page_title' => 'Menu Preview: ' . ($menuData['menu']['menu_name'] ?? 'Unknown')
            ]);

        } catch (Exception $e) {
            error_log('Menu preview error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while previewing menu.');
            $this->redirect('/admin/menus');
        }
    }

    /**
     * Process hierarchy data recursively for database update
     * 
     * @param array<mixed> $hierarchyData Hierarchical data from frontend
     * @param array<mixed> $processedData Processed flat array for database update
     * @param int|null $parentId Parent ID
     * @param int $level Current level (for sort order)
     * @return void
     */
    private function processHierarchyData(array $hierarchyData, array &$processedData, ?int $parentId = null, int $level = 0): void
    {
        $order = 0;
        
        foreach ($hierarchyData as $item) {
            if (!isset($item['id']) || !is_numeric($item['id'])) {
                continue;
            }

            $processedData[] = [
                'item_id' => (int) $item['id'],
                'parent_id' => $parentId,
                'sort_order' => ($level * 100) + (++$order)
            ];

            // Process children recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $this->processHierarchyData($item['children'], $processedData, (int) $item['id'], $level + 1);
            }
        }
    }
}
