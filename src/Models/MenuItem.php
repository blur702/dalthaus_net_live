<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * MenuItem Model
 * 
 * Handles individual menu items with hierarchical relationships
 * and ordering for navigation menus.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class MenuItem extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'menu_items';

    /**
     * Primary key
     */
    protected string $primaryKey = 'item_id';

    /**
     * Link types
     */
    public const LINK_TYPE_PAGE = 'page';
    public const LINK_TYPE_CONTENT = 'content';
    public const LINK_TYPE_CUSTOM = 'custom';
    public const LINK_TYPE_EXTERNAL = 'external';

    /**
     * Get menu items by menu ID
     * 
     * @param int $menuId Menu ID
     * @param bool $hierarchical Return hierarchical structure
     * @return array
     */
    public static function getByMenuId(int $menuId, bool $hierarchical = true): array
    {
        $instance = new static();
        
        $items = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE menu_id = ? ORDER BY sort_order ASC, label ASC",
            [$menuId]
        );

        return $hierarchical ? self::buildHierarchy($items) : $items;
    }

    /**
     * Get menu items for reordering (flat list)
     * 
     * @param int $menuId Menu ID
     * @return array
     */
    public static function getForReordering(int $menuId): array
    {
        $instance = new static();
        
        return $instance->db->fetchAll(
            "SELECT item_id, label, parent_id, sort_order FROM {$instance->table} 
             WHERE menu_id = ? ORDER BY sort_order ASC, label ASC",
            [$menuId]
        );
    }

    /**
     * Build hierarchical structure from flat array
     * 
     * @param array $items Flat array of menu items
     * @param int|null $parentId Parent ID
     * @return array
     */
    private static function buildHierarchy(array $items, ?int $parentId = null): array
    {
        $hierarchy = [];
        
        foreach ($items as $item) {
            if ((int) ($item['parent_id'] ?? 0) === ($parentId ?? 0)) {
                $item['children'] = self::buildHierarchy($items, (int) $item['item_id']);
                $hierarchy[] = $item;
            }
        }

        return $hierarchy;
    }

    /**
     * Create new menu item
     * 
     * @param array $data Menu item data
     * @return static
     */
    public static function createItem(array $data): static
    {
        // Set sort order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = self::getNextSortOrder($data['menu_id'], $data['parent_id'] ?? null);
        }

        return self::create($data);
    }

    /**
     * Get next sort order for menu item
     * 
     * @param int $menuId Menu ID
     * @param int|null $parentId Parent ID
     * @return int
     */
    public static function getNextSortOrder(int $menuId, ?int $parentId = null): int
    {
        $instance = new static();
        
        $query = "SELECT MAX(sort_order) FROM {$instance->table} WHERE menu_id = ?";
        $params = [$menuId];

        if ($parentId !== null) {
            $query .= " AND parent_id = ?";
            $params[] = $parentId;
        } else {
            $query .= " AND parent_id IS NULL";
        }

        $maxOrder = $instance->db->fetchColumn($query, $params);
        return ((int) $maxOrder) + 1;
    }

    /**
     * Update sort order for multiple items
     * 
     * @param array $orderData Array of ['id' => order] pairs
     * @return bool
     */
    public static function updateSortOrder(array $orderData): bool
    {
        $instance = new static();
        
        try {
            $instance->db->beginTransaction();
            
            foreach ($orderData as $id => $order) {
                $instance->db->update(
                    $instance->table,
                    ['sort_order' => $order],
                    'item_id = ?',
                    [$id]
                );
            }
            
            $instance->db->commit();
            return true;
        } catch (\Exception $e) {
            $instance->db->rollback();
            return false;
        }
    }

    /**
     * Update parent relationships for drag-and-drop reordering
     * 
     * @param array $hierarchyData Hierarchical data with parent-child relationships
     * @return bool
     */
    public static function updateHierarchy(array $hierarchyData): bool
    {
        $instance = new static();
        
        try {
            $instance->db->beginTransaction();
            
            foreach ($hierarchyData as $item) {
                $updateData = [
                    'parent_id' => $item['parent_id'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0
                ];
                
                $instance->db->update(
                    $instance->table,
                    $updateData,
                    'item_id = ?',
                    [$item['item_id']]
                );
            }
            
            $instance->db->commit();
            return true;
        } catch (\Exception $e) {
            $instance->db->rollback();
            return false;
        }
    }

    /**
     * Delete menu item and all its children
     * 
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $itemId = $this->getAttribute('item_id');

        try {
            $this->db->beginTransaction();

            // Get all child items recursively
            $childIds = $this->getAllChildIds($itemId);
            
            // Delete all children first
            if (!empty($childIds)) {
                $placeholders = str_repeat('?,', count($childIds) - 1) . '?';
                $this->db->query(
                    "DELETE FROM {$this->table} WHERE item_id IN ({$placeholders})",
                    $childIds
                );
            }

            // Delete the parent item
            $deleted = parent::delete();

            $this->db->commit();
            return $deleted;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Get all child IDs recursively
     * 
     * @param int $parentId Parent item ID
     * @return array
     */
    private function getAllChildIds(int $parentId): array
    {
        $childIds = [];
        
        $children = $this->db->fetchAll(
            "SELECT item_id FROM {$this->table} WHERE parent_id = ?",
            [$parentId]
        );

        foreach ($children as $child) {
            $childIds[] = $child['item_id'];
            // Recursively get grandchildren
            $grandChildIds = $this->getAllChildIds($child['item_id']);
            $childIds = array_merge($childIds, $grandChildIds);
        }

        return $childIds;
    }

    /**
     * Get available content for linking
     * 
     * @return array
     */
    public static function getAvailableContent(): array
    {
        $instance = new static();
        
        // Get published content
        $content = $instance->db->fetchAll(
            "SELECT content_id, title, content_type, url_alias 
             FROM content 
             WHERE status = 'published' 
             ORDER BY content_type, title ASC"
        );

        // Get pages
        $pages = $instance->db->fetchAll(
            "SELECT page_id, title, url_alias 
             FROM pages 
             ORDER BY title ASC"
        );

        return [
            'content' => $content,
            'pages' => $pages
        ];
    }

    /**
     * Generate link URL based on link type and content
     * 
     * @return string
     */
    public function generateUrl(): string
    {
        $link = $this->getAttribute('link');
        
        // If link starts with http or https, return as is
        if (preg_match('/^https?:\/\//', $link)) {
            return $link;
        }

        // If link starts with /, return as is (absolute internal link)
        if (str_starts_with($link, '/')) {
            return $link;
        }

        // Try to determine link type and generate appropriate URL
        if (is_numeric($link)) {
            // Could be content ID or page ID - need to check
            $contentExists = $this->db->exists('content', 'content_id = ? AND status = ?', [$link, 'published']);
            if ($contentExists) {
                $content = $this->db->fetchRow('SELECT content_type, url_alias FROM content WHERE content_id = ?', [$link]);
                if ($content) {
                    return $content['content_type'] === 'article' 
                        ? "/article/{$content['url_alias']}" 
                        : "/photobook/{$content['url_alias']}";
                }
            }

            $pageExists = $this->db->exists('pages', 'page_id = ?', [$link]);
            if ($pageExists) {
                $page = $this->db->fetchRow('SELECT url_alias FROM pages WHERE page_id = ?', [$link]);
                if ($page) {
                    return "/page/{$page['url_alias']}";
                }
            }
        }

        // Return link as is if we can't determine the type
        return $link;
    }

    /**
     * Check if menu item is active based on current URL
     * 
     * @param string $currentUrl Current page URL
     * @return bool
     */
    public function isActive(string $currentUrl): bool
    {
        $itemUrl = $this->generateUrl();
        
        // Exact match
        if ($itemUrl === $currentUrl) {
            return true;
        }

        // Check if current URL starts with item URL (for parent pages)
        if (str_starts_with($currentUrl, $itemUrl) && $itemUrl !== '/') {
            return true;
        }

        return false;
    }

    /**
     * Get menu item with parent information
     * 
     * @return array|null
     */
    public function getWithParent(): ?array
    {
        if (!$this->exists()) {
            return null;
        }

        $data = $this->toArray();
        $parentId = $this->getAttribute('parent_id');

        if ($parentId) {
            $parent = self::find($parentId);
            $data['parent'] = $parent ? $parent->toArray() : null;
        } else {
            $data['parent'] = null;
        }

        return $data;
    }

    /**
     * Get all parent menu items for hierarchy display
     * 
     * @param int $menuId Menu ID
     * @param int|null $excludeId Exclude specific item ID
     * @return array
     */
    public static function getParentOptions(int $menuId, ?int $excludeId = null): array
    {
        $instance = new static();
        
        $query = "SELECT item_id, label, parent_id FROM {$instance->table} WHERE menu_id = ?";
        $params = [$menuId];

        if ($excludeId !== null) {
            $query .= " AND item_id != ?";
            $params[] = $excludeId;
        }

        $query .= " ORDER BY sort_order ASC, label ASC";
        
        $items = $instance->db->fetchAll($query, $params);
        
        // Build hierarchy for display
        return self::buildParentOptions($items);
    }

    /**
     * Build parent options with indentation for hierarchy display
     * 
     * @param array $items Menu items
     * @param int|null $parentId Current parent ID
     * @param int $level Indentation level
     * @return array
     */
    private static function buildParentOptions(array $items, ?int $parentId = null, int $level = 0): array
    {
        $options = [];
        
        foreach ($items as $item) {
            if ((int) ($item['parent_id'] ?? 0) === ($parentId ?? 0)) {
                $indent = str_repeat('— ', $level);
                $options[] = [
                    'value' => $item['item_id'],
                    'label' => $indent . $item['label']
                ];
                
                // Add children
                $children = self::buildParentOptions($items, (int) $item['item_id'], $level + 1);
                $options = array_merge($options, $children);
            }
        }

        return $options;
    }

    /**
     * Validate menu item data
     * 
     * @param array $data Menu item data
     * @return array Array of validation errors
     */
    public static function validateItemData(array $data): array
    {
        $errors = [];

        // Label validation
        if (empty($data['label'])) {
            $errors['label'] = 'Label is required';
        } elseif (strlen($data['label']) > 100) {
            $errors['label'] = 'Label must be less than 100 characters';
        }

        // Link validation
        if (empty($data['link'])) {
            $errors['link'] = 'Link is required';
        } elseif (strlen($data['link']) > 255) {
            $errors['link'] = 'Link must be less than 255 characters';
        }

        // Menu ID validation
        if (empty($data['menu_id'])) {
            $errors['menu_id'] = 'Menu ID is required';
        }

        // Sort order validation
        if (isset($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'] = 'Sort order must be a non-negative number';
        }

        return $errors;
    }

    /**
     * Get menu item breadcrumb path
     * 
     * @return array
     */
    public function getBreadcrumbPath(): array
    {
        $path = [];
        $current = $this;

        while ($current) {
            array_unshift($path, [
                'label' => $current->getAttribute('label'),
                'url' => $current->generateUrl()
            ]);

            $parentId = $current->getAttribute('parent_id');
            $current = $parentId ? self::find($parentId) : null;
        }

        return $path;
    }
}
