<?php
/**
 * Category Model
 * نموذج التصنيف
 */

require_once __DIR__ . '/../config/database.php';

class Category {
    private $table = 'categories';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Find category by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all categories
     */
    public function getAll($includeInactive = false) {
        $where = $includeInactive ? "" : "WHERE is_active = 1";
        $stmt = $this->db->query("SELECT * FROM {$this->table} {$where} ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get categories with product count
     */
    public function getWithProductCount() {
        $sql = "SELECT c.*, COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get parent categories (top level)
     */
    public function getParentCategories() {
        $sql = "SELECT * FROM {$this->table}
                WHERE parent_id IS NULL AND is_active = 1
                ORDER BY sort_order ASC, name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get child categories
     */
    public function getChildren($parentId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }

    /**
     * Get category tree
     */
    public function getTree() {
        $categories = $this->getAll();
        return $this->buildTree($categories);
    }

    /**
     * Build category tree
     */
    private function buildTree($categories, $parentId = null) {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $category['children'] = $this->buildTree($categories, $category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Get category breadcrumb
     */
    public function getBreadcrumb($id) {
        $breadcrumb = [];
        $category = $this->findById($id);

        while ($category) {
            array_unshift($breadcrumb, $category);
            $category = $category['parent_id'] ? $this->findById($category['parent_id']) : null;
        }

        return $breadcrumb;
    }

    /**
     * Get all descendant IDs
     */
    public function getAllDescendantIds($id) {
        $ids = [$id];
        $children = $this->getChildren($id);

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getAllDescendantIds($child['id']));
        }

        return $ids;
    }
}
