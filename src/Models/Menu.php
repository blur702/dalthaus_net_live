<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * Menu Model
 * 
 * Handles menu groups and their associated menu items
 * for site navigation management.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class Menu extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'menus';

    /**
     * Primary key
     */
    protected string $primaryKey = 'menu_id';

    /**
     * Find menu by name
     * 
     * @param string $menuName Menu name
     * @return static|null
     */
    public static function findByName(string $menuName): ?static
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table} WHERE menu_name = ?";
        return self::queryFirst($query, [$menuName]);
    }

    /**
     * Get all menus with item counts
     * 
     * @return array
     */
    public static function getAllWithCounts(): array
    {
        $instance = new static();
        
        $query = "SELECT m.*, COUNT(mi.item_id) as item_count
                  FROM {$instance->table} m
                  LEFT JOIN menu_items mi ON m.menu_id = mi.menu_id
                  GROUP BY m.menu_id
                  ORDER BY m.menu_name ASC";

        $results = self::query($query);
        
        // Convert model objects to arrays for views
        return array_map(function($menu) {
            if (is_object($menu)) {
                return $menu->toArray();
            }
            return $menu;
        }, $results);
    }

    /**
     * Get menu with all its items (hierarchical)
     * 
     * @param int $menuId Menu ID
     * @return array|null
     */
    public static function getWithItems(int $menuId): ?array
    {
        $instance = new static();
        
        // Get menu details
        $menu = self::find($menuId);
        if (!$menu) {
            return null;
        }

        // Get all menu items for this menu
        $items = $instance->db->fetchAll(
            "SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC, label ASC",
            [$menuId]
        );

        return [
            'menu' => $menu->toArray(),
            'items' => self::buildMenuHierarchy($items)
        ];
    }

    /**
     * Get menu by name with all its items (hierarchical)
     * 
     * @param string $menuName Menu name
     * @return array|null
     */
    public static function getByNameWithItems(string $menuName): ?array
    {
        $menu = self::findByName($menuName);
        if (!$menu) {
            return null;
        }

        return self::getWithItems($menu->menu_id);
    }

    /**
     * Build hierarchical menu structure from flat array
     * 
     * @param array $items Flat array of menu items
     * @param int|null $parentId Parent ID to build children for
     * @return array
     */
    private static function buildMenuHierarchy(array $items, ?int $parentId = null): array
    {
        $hierarchy = [];
        
        foreach ($items as $item) {
            if ((int) $item['parent_id'] === $parentId) {
                $item['children'] = self::buildMenuHierarchy($items, (int) $item['item_id']);
                $hierarchy[] = $item;
            }
        }

        return $hierarchy;
    }

    /**
     * Check if menu name is available
     * 
     * @param string $menuName Menu name
     * @param int|null $excludeMenuId Menu ID to exclude (for updates)
     * @return bool
     */
    public static function isNameAvailable(string $menuName, ?int $excludeMenuId = null): bool
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} WHERE menu_name = ?";
        $params = [$menuName];

        if ($excludeMenuId !== null) {
            $query .= " AND menu_id != ?";
            $params[] = $excludeMenuId;
        }

        $count = (int) $instance->db->fetchColumn($query, $params);
        return $count === 0;
    }

    /**
     * Get menu items count
     * 
     * @return int
     */
    public function getItemsCount(): int
    {
        $menuId = $this->getAttribute('menu_id');
        if (!$menuId) {
            return 0;
        }

        return $this->db->count('menu_items', 'menu_id = ?', [$menuId]);
    }

    /**
     * Delete menu and all its items
     * 
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $menuId = $this->getAttribute('menu_id');

        try {
            $this->db->beginTransaction();

            // Delete all menu items first (foreign key constraint)
            $this->db->delete('menu_items', 'menu_id = ?', [$menuId]);

            // Delete the menu
            $deleted = parent::delete();

            $this->db->commit();
            return $deleted;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Validate menu data
     * 
     * @param array $data Menu data
     * @param int|null $excludeMenuId Menu ID to exclude (for updates)
     * @return array Array of validation errors
     */
    public static function validateMenuData(array $data, ?int $excludeMenuId = null): array
    {
        $errors = [];

        // Menu name validation
        if (empty($data['menu_name'])) {
            $errors['menu_name'] = 'Menu name is required';
        } elseif (strlen($data['menu_name']) > 100) {
            $errors['menu_name'] = 'Menu name must be less than 100 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['menu_name'])) {
            $errors['menu_name'] = 'Menu name can only contain letters, numbers, underscores, and hyphens';
        } elseif (!self::isNameAvailable($data['menu_name'], $excludeMenuId)) {
            $errors['menu_name'] = 'Menu name is already taken';
        }

        return $errors;
    }

    /**
     * Create menu with default items
     * 
     * @param string $menuName Menu name
     * @param array $defaultItems Array of default menu items
     * @return static|null
     */
    public static function createWithItems(string $menuName, array $defaultItems = []): ?static
    {
        $instance = new static();

        try {
            $instance->db->beginTransaction();

            // Create menu
            $menu = self::create(['menu_name' => $menuName]);

            // Create default items
            if (!empty($defaultItems)) {
                $menuItemModel = new MenuItem();
                foreach ($defaultItems as $index => $item) {
                    $item['menu_id'] = $menu->menu_id;
                    $item['sort_order'] = $item['sort_order'] ?? ($index + 1);
                    $menuItemModel->db->insert('menu_items', $item);
                }
            }

            $instance->db->commit();
            return $menu;
        } catch (\Exception $e) {
            $instance->db->rollback();
            return null;
        }
    }

    /**
     * Get available menu positions/locations
     * 
     * @return array
     */
    public static function getAvailableLocations(): array
    {
        return [
            'main' => 'Main Navigation',
            'footer' => 'Footer Navigation',
            'sidebar' => 'Sidebar Navigation',
            'top' => 'Top Navigation'
        ];
    }

    /**
     * Duplicate menu with all items
     * 
     * @param string $newName New menu name
     * @return static|null
     */
    public function duplicate(string $newName): ?static
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            $this->db->beginTransaction();

            // Create new menu
            $newMenu = self::create(['menu_name' => $newName]);

            // Get all items from current menu
            $items = $this->db->fetchAll(
                "SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC",
                [$this->getAttribute('menu_id')]
            );

            // Create mapping of old IDs to new IDs for parent_id updates
            $idMapping = [];

            // First pass: create all items without parent_id
            foreach ($items as $item) {
                $newItemData = $item;
                unset($newItemData['item_id']);
                $newItemData['menu_id'] = $newMenu->menu_id;
                $newItemData['parent_id'] = null; // Will be set in second pass

                $newItemId = $this->db->insert('menu_items', $newItemData);
                $idMapping[$item['item_id']] = $newItemId;
            }

            // Second pass: update parent_id relationships
            foreach ($items as $item) {
                if ($item['parent_id'] !== null) {
                    $newParentId = $idMapping[$item['parent_id']] ?? null;
                    if ($newParentId) {
                        $this->db->update(
                            'menu_items',
                            ['parent_id' => $newParentId],
                            'item_id = ?',
                            [$idMapping[$item['item_id']]]
                        );
                    }
                }
            }

            $this->db->commit();
            return $newMenu;
        } catch (\Exception $e) {
            $this->db->rollback();
            return null;
        }
    }
}
