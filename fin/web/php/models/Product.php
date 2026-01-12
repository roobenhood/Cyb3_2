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
     * Get all products with filters
     */
    public function getAll($page = 1, $perPage = 12, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ['p.is_active = 1'];
        $params = [];
        $orderBy = 'p.created_at DESC';

        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        // Vendor filter
        if (!empty($filters['vendor_id'])) {
            $where[] = "p.vendor_id = ?";
            $params[] = $filters['vendor_id'];
        }

        // Featured filter
        if (isset($filters['is_featured'])) {
            $where[] = "p.is_featured = ?";
            $params[] = $filters['is_featured'];
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $where[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }

        // In stock filter
        if (!empty($filters['in_stock'])) {
            $where[] = "p.stock > 0";
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.name_en LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        // Sorting
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_high':
                    $orderBy = 'p.price DESC';
                    break;
                case 'newest':
                    $orderBy = 'p.created_at DESC';
                    break;
                case 'oldest':
                    $orderBy = 'p.created_at ASC';
                    break;
                case 'popular':
                    $orderBy = 'p.sales_count DESC';
                    break;
                case 'rating':
                    $orderBy = 'p.rating DESC';
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get products
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT p.id, p.name, p.name_en, p.slug, p.short_description, p.price, 
                       p.discount_price, p.image, p.stock, p.rating, p.review_count,
                       p.is_featured, p.sales_count, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        return [
            'products' => $products,
            'total' => $total
        ];
    }

    /**
     * Get featured products
     */
    public function getFeatured($limit = 8) {
        $sql = "SELECT p.id, p.name, p.slug, p.price, p.discount_price, p.image, 
                       p.rating, p.review_count, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get related products
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.id, p.name, p.slug, p.price, p.discount_price, p.image, 
                       p.rating, p.review_count
                FROM {$this->table} p
                WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ?
                ORDER BY RAND()
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Create product
     */
    public function create($data) {
        $slug = $this->generateSlug($data['name']);
        $images = isset($data['images']) ? json_encode($data['images']) : null;

        $sql = "INSERT INTO {$this->table} 
                (name, name_en, slug, description, short_description, category_id, vendor_id,
                 sku, barcode, price, discount_price, cost_price, stock, min_stock,
                 weight, unit, image, images, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['name_en'] ?? null,
            $slug,
            $data['description'],
            $data['short_description'] ?? null,
            $data['category_id'],
            $data['vendor_id'] ?? null,
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['price'],
            $data['discount_price'] ?? null,
            $data['cost_price'] ?? null,
            $data['stock'] ?? 0,
            $data['min_stock'] ?? 5,
            $data['weight'] ?? null,
            $data['unit'] ?? 'piece',
            $data['image'] ?? null,
            $images,
            $data['is_featured'] ?? 0
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    /**
     * Update product
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        $allowedFields = [
            'name', 'name_en', 'description', 'short_description', 'category_id',
            'sku', 'barcode', 'price', 'discount_price', 'cost_price', 'stock',
            'min_stock', 'weight', 'unit', 'image', 'is_featured', 'is_active'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['images'])) {
            $fields[] = "images = ?";
            $values[] = json_encode($data['images']);
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
     * Delete product
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Update stock
     */
    public function updateStock($id, $quantity, $operation = 'subtract') {
        $operator = $operation === 'add' ? '+' : '-';
        $sql = "UPDATE {$this->table} SET stock = stock {$operator} ? WHERE id = ? AND stock >= ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $id, $operation === 'subtract' ? $quantity : 0]);
    }

    /**
     * Increment views
     */
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET views = views + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get product variants
     */
    public function getVariants($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $variants = $stmt->fetchAll();

        foreach ($variants as &$variant) {
            $variant['attributes'] = $variant['attributes'] ? json_decode($variant['attributes'], true) : [];
        }

        return $variants;
    }

    /**
     * Generate slug
     */
    private function generateSlug($name) {
        $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $name);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = mb_strtolower($slug);

        // Check uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Update product rating
     */
    public function updateRating($productId) {
        $sql = "UPDATE {$this->table} p
                SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND is_approved = 1)
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$productId]);
    }
}
