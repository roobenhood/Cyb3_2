<?php
/**
 * Product Model
 * نموذج المنتج
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $table = 'products';
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Find product by ID
     */
    public function findById($id) {
        $sql = "SELECT p.*, c.name as category_name, c.name_en as category_name_en,
                       u.name as vendor_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.vendor_id = u.id
                WHERE p.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
            $product['variants'] = $this->getVariants($id);
        }

        return $product;
    }

    /**
     * Find product by slug
     */
    public function findBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, u.name as vendor_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.vendor_id = u.id
                WHERE p.slug = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $product = $stmt->fetch();

        if ($product) {
            $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
            $product['variants'] = $this->getVariants($product['id']);
        }

        return $product;
    }

    /**
     * Get all products with pagination and filters
     */
    public function getAll($page = 1, $perPage = 12, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["p.is_active = 1"];
        $params = [];

        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Price range
        if (!empty($filters['min_price'])) {
            $where[] = "COALESCE(p.discount_price, p.price) >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = "COALESCE(p.discount_price, p.price) <= ?";
            $params[] = $filters['max_price'];
        }

        // In stock
        if (!empty($filters['in_stock'])) {
            $where[] = "p.stock > 0";
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = "p.created_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = "COALESCE(p.discount_price, p.price) ASC";
                    break;
                case 'price_desc':
                    $orderBy = "COALESCE(p.discount_price, p.price) DESC";
                    break;
                case 'rating':
                    $orderBy = "p.rating DESC";
                    break;
                case 'popular':
                    $orderBy = "p.sales_count DESC";
                    break;
                case 'newest':
                    $orderBy = "p.created_at DESC";
                    break;
            }
        }

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get products
        $sql = "SELECT p.*, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
        }

        return [
            'products' => $products,
            'total' => $total
        ];
    }

    /**
     * Get featured products
     */
    public function getFeatured($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
        }

        return $products;
    }

    /**
     * Get related products
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ?
                ORDER BY RAND()
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId, $productId, $limit]);
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
        }

        return $products;
    }

    /**
     * Get product variants
     */
    public function getVariants($productId) {
        $sql = "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $variants = $stmt->fetchAll();

        foreach ($variants as &$variant) {
            $variant['attributes'] = $variant['attributes'] ? json_decode($variant['attributes'], true) : [];
        }

        return $variants;
    }

    /**
     * Increment views
     */
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Update rating
     */
    public function updateRating($id) {
        $sql = "UPDATE {$this->table} p
                SET p.rating = (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1),
                    p.review_count = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1)
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Check stock
     */
    public function checkStock($id, $quantity, $variantId = null) {
        if ($variantId) {
            $stmt = $this->db->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ?");
            $stmt->execute([$variantId, $id]);
        } else {
            $stmt = $this->db->prepare("SELECT stock FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
        }
        $result = $stmt->fetch();
        return $result && $result['stock'] >= $quantity;
    }

    /**
     * Reduce stock
     */
    public function reduceStock($id, $quantity, $variantId = null) {
        if ($variantId) {
            $stmt = $this->db->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND product_id = ?");
            $stmt->execute([$quantity, $variantId, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $id]);
        }
    }

    /**
     * Restore stock
     */
    public function restoreStock($id, $quantity, $variantId = null) {
        if ($variantId) {
            $stmt = $this->db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ? AND product_id = ?");
            $stmt->execute([$quantity, $variantId, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$quantity, $id]);
        }
    }
}
