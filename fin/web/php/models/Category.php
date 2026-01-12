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
    public function getChildCategories($parentId) {
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
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Create category
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, name_en, description, image, icon, parent_id, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['name_en'] ?? null,
            $data['description'] ?? null,
            $data['image'] ?? null,
            $data['icon'] ?? null,
            $data['parent_id'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    /**
     * Update category
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'name_en', 'description', 'image', 'icon', 'parent_id', 'is_active', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Delete category
     */
    public function delete($id) {
        // Check if has products
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Cannot delete, has products
        }

        // Check if has children
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parent_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Cannot delete, has children
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        return $this->findById($id);
    }
}
