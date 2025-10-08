<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'sku', 'price', 'compare_price',
        'cost_price', 'track_inventory', 'inventory_quantity', 'allow_backorder', 'weight',
        'dimensions', 'category_id', 'brand_id', 'status', 'is_featured', 'is_new', 'is_on_sale', 'coming_soon',
        'main_image', 'gallery', 'ingredients', 'how_to_use', 'benefits', 'skin_type',
        'shade_range', 'meta_title', 'meta_description'
    ];
    
    public function getAll($filters = [], $orderBy = 'created_at DESC') {
        $conditions = [];
        
        // Si se pasan filtros como array asociativo
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        return $this->findAll($conditions, $orderBy, $filters['limit'] ?? null);
    }
    
    public function getFeaturedProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND p.is_featured = 1 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getNewProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND p.is_new = 1 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getOnSaleProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       CASE 
                           WHEN p.compare_price > 0 AND p.compare_price > p.price 
                           THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                           ELSE 0 
                       END as discount_percentage
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND p.is_on_sale = 1 
                ORDER BY discount_percentage DESC, p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getNewestProducts($limit = 10, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getTopRatedProducts($limit = 10, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(AVG(r.rating), 0) as average_rating,
                       COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.status = 'active'
                GROUP BY p.id, c.name, b.name
                ORDER BY average_rating DESC, review_count DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getPopularProducts($limit = 10, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(AVG(r.rating), 0) as average_rating,
                       COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.status = 'active'
                GROUP BY p.id, c.name, b.name
                ORDER BY p.views DESC, review_count DESC, average_rating DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getProductsByCategory($categoryId, $limit = null, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND p.category_id = ?";
        
        $params = [$categoryId];
        
        // Aplicar filtros adicionales
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand_id'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Ordenamiento
        $orderBy = $filters['sort'] ?? 'newest';
        switch ($orderBy) {
            case 'price_low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name':
                $sql .= " ORDER BY p.name ASC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener la imagen principal de un producto
     * Primero intenta main_image, luego busca en product_images
     */
    public function getProductMainImage($productId) {
        // Primero verificar main_image del producto
        $stmt = $this->db->prepare("SELECT main_image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product && !empty($product['main_image'])) {
            return $product['main_image'];
        }
        
        // Si no tiene main_image, buscar en product_images
        $stmt = $this->db->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY sort_order ASC LIMIT 1");
        $stmt->execute([$productId]);
        $image = $stmt->fetch();
        
        return $image ? $image['image_path'] : null;
    }

    public function searchProducts($query, $filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(p.main_image, pi.image_path) as main_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' AND (
                    p.name LIKE ? OR 
                    p.description LIKE ? OR 
                    p.short_description LIKE ? OR
                    b.name LIKE ? OR
                    c.name LIKE ?
                )";
        
        $searchTerm = "%$query%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        $sql .= " ORDER BY 
                    CASE 
                        WHEN p.name LIKE ? THEN 1
                        WHEN p.name LIKE ? THEN 2
                        ELSE 3
                    END,
                    p.is_featured DESC,
                    p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $exactMatch = "$query%";
        $startsWith = "$query%";
        $params[] = $exactMatch;
        $params[] = $startsWith;
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getProductWithDetails($id) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                       b.name as brand_name, b.slug as brand_slug,
                       COALESCE(p.main_image, pi.image_path) as main_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.id = ? AND p.status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $product = $stmt->fetch();
        if ($product) {
            // Incrementar vistas
            $this->increment($id, 'views');
            
            // Obtener todas las imágenes del producto
            $imageStmt = $this->db->prepare("SELECT image_path, alt_text, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
            $imageStmt->execute([$id]);
            $product['images'] = $imageStmt->fetchAll();
        }
        
        return $product;
    }
    
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(p.main_image, pi.image_path) as main_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' AND p.category_id = ? AND p.id != ?
                ORDER BY RAND() 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function updateInventory($productId, $quantity) {
        $sql = "UPDATE products SET inventory_quantity = inventory_quantity - ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $productId]);
    }
    
    public function checkStock($productId, $quantity = 1) {
        $sql = "SELECT inventory_quantity FROM products WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        
        $result = $stmt->fetch();
        return $result && $result['inventory_quantity'] >= $quantity;
    }
    
    public function getProductsWithFilters($filters = [], $limit = 20, $offset = 0) {
        // Check if session is already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name";
        
        // Add wishlist check if user is logged in
        if ($userId) {
            $sql .= ", (SELECT COUNT(*) FROM wishlists w WHERE w.customer_id = ? AND w.product_id = p.id) as in_wishlist";
        } else {
            $sql .= ", 0 as in_wishlist";
        }
        
        $sql .= " FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE 1=1";
        
        $params = [];
        
        // Add user ID parameter if logged in
        if ($userId) {
            $params[] = $userId;
        }
        
        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['stock']) && $filters['stock'] === 'low') {
            $sql .= " AND p.inventory_quantity <= 5";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function countProductsWithFilters($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE 1=1";
        
        $params = [];
        
        // Apply same filters as getProductsWithFilters
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['stock']) && $filters['stock'] === 'low') {
            $sql .= " AND p.inventory_quantity <= 5";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function addProductImage($productId, $imagePath) {
        // For now, we'll store additional images in the gallery JSON field
        $product = $this->findById($productId);
        if (!$product) {
            return false;
        }
        
        $gallery = $product['gallery'] ? json_decode($product['gallery'], true) : [];
        $gallery[] = $imagePath;
        
        return $this->update($productId, ['gallery' => json_encode($gallery)]);
    }
    
    public function addProductAttribute($productId, $name, $value) {
        // For this basic implementation, we'll store attributes in a simple way
        // In a more complex system, you'd have a separate product_attributes table
        
        // For now, we can store them in the product description or create a simple implementation
        // This is a placeholder - you might want to create a proper attributes system later
        return true; // Placeholder return
    }
    
    public function getProductById($id) {
        return $this->findById($id);
    }
    
    // Bulk Actions Methods
    public function bulkUpdateStatus($productIds, $status) {
        if (empty($productIds) || !in_array($status, ['active', 'draft', 'archived'])) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $productIds);
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function bulkDelete($productIds) {
        if (empty($productIds)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            // Delete related records first if needed (order_items, cart_items, etc.)
            // For now, we'll do a simple delete
            $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($productIds);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function bulkUpdate($productIds, $updates) {
        if (empty($productIds) || empty($updates)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($productIds as $productId) {
                $this->processBulkUpdateForProduct($productId, $updates);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function processBulkUpdateForProduct($productId, $updates) {
        $product = $this->findById($productId);
        if (!$product) {
            return false;
        }
        
        $updateData = [];
        
        // Handle simple field updates
        if (isset($updates['category_id']) && $updates['category_id'] !== '') {
            $updateData['category_id'] = $updates['category_id'];
        }
        
        if (isset($updates['brand_id']) && $updates['brand_id'] !== '') {
            $updateData['brand_id'] = $updates['brand_id'];
        }
        
        if (isset($updates['status'])) {
            $updateData['status'] = $updates['status'];
        }
        
        if (isset($updates['featured'])) {
            $updateData['is_featured'] = $updates['featured'];
        }
        
        // Handle price updates
        if (isset($updates['price_action']) && isset($updates['price_value']) && $updates['price_value'] !== '') {
            $currentPrice = floatval($product['price']);
            $value = floatval($updates['price_value']);
            $action = $updates['price_action'];
            $type = $updates['price_type'] ?? 'fixed';
            
            switch ($action) {
                case 'increase':
                    if ($type === 'percent') {
                        $updateData['price'] = $currentPrice * (1 + $value / 100);
                    } else {
                        $updateData['price'] = $currentPrice + $value;
                    }
                    break;
                    
                case 'decrease':
                    if ($type === 'percent') {
                        $updateData['price'] = $currentPrice * (1 - $value / 100);
                    } else {
                        $updateData['price'] = max(0, $currentPrice - $value);
                    }
                    break;
                    
                case 'set':
                    $updateData['price'] = $value;
                    break;
            }
        }
        
        // Handle stock updates
        if (isset($updates['stock_action']) && isset($updates['stock_value']) && $updates['stock_value'] !== '') {
            $currentStock = intval($product['inventory_quantity'] ?? 0);
            $value = intval($updates['stock_value']);
            $action = $updates['stock_action'];
            
            switch ($action) {
                case 'increase':
                    $updateData['inventory_quantity'] = $currentStock + $value;
                    break;
                    
                case 'decrease':
                    $updateData['inventory_quantity'] = max(0, $currentStock - $value);
                    break;
                    
                case 'set':
                    $updateData['inventory_quantity'] = $value;
                    break;
            }
        }
        
        // Update the product if there are changes
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            return $this->update($productId, $updateData);
        }
        
        return true;
    }
    
    public function getBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.slug = ? AND p.status = 'active'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    public function countByCategory($categoryId, $filters = []) {
        $sql = "SELECT COUNT(*) as total 
                FROM products p 
                WHERE p.status = 'active' AND p.category_id = ?";
        
        $params = [$categoryId];
        
        // Aplicar filtros adicionales
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['total'] : 0;
    }
    
    // Products on sale methods
    public function getProductsOnSale($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       CASE 
                           WHEN p.compare_price > 0 AND p.compare_price > p.price 
                           THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                           ELSE 0 
                       END as discount_percentage
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND (
                    p.is_on_sale = 1 OR 
                    (p.compare_price IS NOT NULL AND p.compare_price > p.price)
                )";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['min_discount'])) {
            $sql .= " AND ((p.compare_price - p.price) / p.compare_price * 100) >= ?";
            $params[] = $filters['min_discount'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Sorting
        switch ($filters['sort'] ?? 'discount_desc') {
            case 'discount_asc':
                $sql .= " ORDER BY ((p.compare_price - p.price) / p.compare_price * 100) ASC";
                break;
            case 'price_low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            default: // discount_desc
                $sql .= " ORDER BY ((p.compare_price - p.price) / p.compare_price * 100) DESC";
                break;
        }
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting products on sale: " . $e->getMessage());
            return [];
        }
    }
    
    public function countProductsOnSale($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND (
                    p.is_on_sale = 1 OR 
                    (p.compare_price IS NOT NULL AND p.compare_price > p.price)
                )";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['min_discount'])) {
            $sql .= " AND ((p.compare_price - p.price) / p.compare_price * 100) >= ?";
            $params[] = $filters['min_discount'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error counting products on sale: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getAverageDiscount() {
        $sql = "SELECT AVG((compare_price - price) / compare_price * 100) as avg_discount 
                FROM products 
                WHERE status = 'active' AND compare_price IS NOT NULL AND compare_price > price";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['avg_discount'] ?? 0, 1);
        } catch (PDOException $e) {
            error_log("Error getting average discount: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getMaxDiscount() {
        $sql = "SELECT MAX((compare_price - price) / compare_price * 100) as max_discount 
                FROM products 
                WHERE status = 'active' AND compare_price IS NOT NULL AND compare_price > price";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['max_discount'] ?? 0, 0);
        } catch (PDOException $e) {
            error_log("Error getting max discount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Actualizar stock con registro de historial
     */
    public function updateStockWithHistory($productId, $newQuantity, $reason = 'Manual update', $options = []) {
        try {
            // Obtener cantidad actual
            $currentProduct = $this->findById($productId);
            if (!$currentProduct) {
                return false;
            }
            
            $quantityBefore = $currentProduct['inventory_quantity'];
            $quantityChange = $newQuantity - $quantityBefore;
            
            // Actualizar el stock en el producto
            $updated = $this->update($productId, ['inventory_quantity' => $newQuantity]);
            
            if ($updated) {
                // Registrar en el historial si hay cambio en la cantidad
                if ($quantityChange != 0) {
                    require_once __DIR__ . '/InventoryHistory.php';
                    $inventoryHistory = new InventoryHistory();
                    
                    $movementType = 'adjustment';
                    if ($quantityChange > 0) {
                        $movementType = 'in';
                    } elseif ($quantityChange < 0) {
                        $movementType = 'out';
                    }
                    
                    $inventoryHistory->recordMovement(
                        $productId,
                        $movementType,
                        $quantityChange,
                        $quantityBefore,
                        $newQuantity,
                        array_merge($options, [
                            'reason' => $reason,
                            'reference_type' => $options['reference_type'] ?? 'adjustment'
                        ])
                    );
                }
            }
            
            return $updated;
            
        } catch (Exception $e) {
            error_log("Error updating stock with history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reducir stock por venta
     */
    public function reduceStockBySale($productId, $quantity, $orderId = null) {
        try {
            $currentProduct = $this->findById($productId);
            if (!$currentProduct) {
                return false;
            }
            
            $quantityBefore = $currentProduct['inventory_quantity'];
            $quantityAfter = $quantityBefore - $quantity;
            
            // Verificar que hay suficiente stock
            if ($quantityAfter < 0 && !$currentProduct['allow_backorder']) {
                return false;
            }
            
            // Actualizar stock
            $updated = $this->update($productId, ['inventory_quantity' => $quantityAfter]);
            
            if ($updated) {
                // Registrar en el historial
                require_once __DIR__ . '/InventoryHistory.php';
                $inventoryHistory = new InventoryHistory();
                
                $inventoryHistory->recordMovement(
                    $productId,
                    'out',
                    -$quantity,
                    $quantityBefore,
                    $quantityAfter,
                    [
                        'reason' => 'Venta',
                        'reference_type' => 'sale',
                        'reference_id' => $orderId
                    ]
                );
            }
            
            return $updated;
            
        } catch (Exception $e) {
            error_log("Error reducing stock by sale: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // MÉTODOS PARA MANEJO DE MÚLTIPLES IMÁGENES
    // ============================================

    /**
     * Obtener todas las imágenes de un producto
     */
    public function getProductImages($productId) {
        try {
            $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting product images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener la imagen principal de un producto
     */
    public function getPrimaryImage($productId) {
        try {
            $sql = "SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            $primary = $stmt->fetch();
            
            // Si no hay imagen principal, tomar la primera
            if (!$primary) {
                $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$productId]);
                $primary = $stmt->fetch();
            }
            
            return $primary;
        } catch (Exception $e) {
            error_log("Error getting primary image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Agregar una nueva imagen al producto
     */
    public function addProductImageNew($productId, $imagePath, $altText = '', $isPrimary = false) {
        try {
            // Si es imagen principal, quitar primary de las demás
            if ($isPrimary) {
                $this->removePrimaryFlag($productId);
            }

            // Obtener el siguiente sort_order
            $sql = "SELECT COALESCE(MAX(sort_order), -1) + 1 as next_order FROM product_images WHERE product_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            $sortOrder = $result['next_order'];

            // Insertar la nueva imagen
            $sql = "INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$productId, $imagePath, $altText, $sortOrder, $isPrimary ? 1 : 0]);
            
        } catch (Exception $e) {
            error_log("Error adding product image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar una imagen del producto
     */
    public function removeProductImage($imageId) {
        try {
            // Obtener info de la imagen antes de eliminar
            $sql = "SELECT * FROM product_images WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();
            
            if (!$image) {
                return false;
            }

            // Eliminar de la base de datos
            $sql = "DELETE FROM product_images WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $deleted = $stmt->execute([$imageId]);

            // Si era imagen principal, asignar primary a la siguiente
            if ($deleted && $image['is_primary']) {
                $this->assignNewPrimary($image['product_id']);
            }

            // Eliminar archivo físico
            if ($deleted && file_exists($image['image_path'])) {
                unlink($image['image_path']);
            }

            return $deleted;
            
        } catch (Exception $e) {
            error_log("Error removing product image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar el orden de las imágenes
     */
    public function updateImageOrder($productId, $imageOrders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($imageOrders as $imageId => $order) {
                $sql = "UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$order, $imageId, $productId]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating image order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Establecer imagen como principal
     */
    public function setPrimaryImage($productId, $imageId) {
        try {
            $this->db->beginTransaction();
            
            // Quitar primary de todas las imágenes del producto
            $this->removePrimaryFlag($productId);
            
            // Establecer la nueva como primary
            $sql = "UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$imageId, $productId]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error setting primary image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar imágenes de un producto
     */
    public function countProductImages($productId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM product_images WHERE product_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error counting product images: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Métodos auxiliares privados
     */
    private function removePrimaryFlag($productId) {
        $sql = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$productId]);
    }

    private function assignNewPrimary($productId) {
        $sql = "UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$productId]);
    }

    /**
     * Método mejorado para obtener productos con imágenes
     */
    public function getProductWithImages($productId) {
        $product = $this->findById($productId);
        if ($product) {
            $product['images'] = $this->getProductImages($productId);
            $product['primary_image'] = $this->getPrimaryImage($productId);
        }
        return $product;
    }
    
    /**
     * Búsqueda avanzada con filtros múltiples
     */
    public function advancedSearch($query = '', $filters = [], $sort = 'relevance', $limit = 12, $offset = 0) {
        $conditions = [];
        $params = [];
        
        // Base query
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(AVG(r.rating), 0) as average_rating,
                       COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN reviews r ON p.id = r.product_id";
        
        // Base condition
        $conditions[] = "p.status = 'active'";
        
        // Search query
        if (!empty($query)) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR 
                            b.name LIKE ? OR c.name LIKE ? OR p.sku LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Category filter
        if (!empty($filters['categories'])) {
            $placeholders = str_repeat('?,', count($filters['categories']) - 1) . '?';
            $conditions[] = "p.category_id IN ($placeholders)";
            $params = array_merge($params, $filters['categories']);
        }
        
        // Brand filter
        if (!empty($filters['brands'])) {
            $placeholders = str_repeat('?,', count($filters['brands']) - 1) . '?';
            $conditions[] = "p.brand_id IN ($placeholders)";
            $params = array_merge($params, $filters['brands']);
        }
        
        // Price filters
        if (!empty($filters['min_price'])) {
            $conditions[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $conditions[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Discount filter
        if (!empty($filters['has_discount'])) {
            $conditions[] = "(p.compare_price > 0 AND p.compare_price > p.price)";
        }
        
        // Combine conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // Group by for aggregate functions
        $sql .= " GROUP BY p.id, c.name, b.name";
        
        // Rating filter (after GROUP BY)
        if (!empty($filters['min_rating'])) {
            $sql .= " HAVING average_rating >= ?";
            $params[] = $filters['min_rating'];
        }
        
        // Sorting
        switch ($sort) {
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'price-low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price-high':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY average_rating DESC, review_count DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY p.views DESC, review_count DESC";
                break;
            case 'relevance':
            default:
                if (!empty($query)) {
                    $sql .= " ORDER BY 
                        CASE 
                            WHEN p.name LIKE ? THEN 1
                            WHEN p.short_description LIKE ? THEN 2
                            WHEN b.name LIKE ? THEN 3
                            WHEN c.name LIKE ? THEN 4
                            ELSE 5
                        END,
                        p.is_featured DESC,
                        average_rating DESC";
                    $searchTerm = "%$query%";
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                } else {
                    $sql .= " ORDER BY p.is_featured DESC, average_rating DESC, p.created_at DESC";
                }
                break;
        }
        
        // Get total count for pagination
        $countSql = str_replace(
            "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(AVG(r.rating), 0) as average_rating,
                       COUNT(r.id) as review_count",
            "SELECT COUNT(DISTINCT p.id) as total",
            $sql
        );
        
        // Remove ORDER BY clause for count query
        $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
        // Remove HAVING clause for count query if it exists
        $countSql = preg_replace('/HAVING.*$/', '', $countSql);
        
        $countStmt = $this->db->prepare($countSql);
        $countParams = $params;
        
        // Remove relevance ordering parameters from count query
        if ($sort === 'relevance' && !empty($query)) {
            $countParams = array_slice($params, 0, -4);
        }
        
        $countStmt->execute($countParams);
        $totalProducts = $countStmt->fetch()['total'] ?? 0;
        
        // Add pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        return [
            'products' => $products,
            'total' => $totalProducts
        ];
    }
    
    /**
     * Búsqueda optimizada para tiempo real
     */
    public function searchProductsRealtime($query, $limit = 8) {
        // Use MATCH AGAINST for better full-text search if available
        $sql = "SELECT p.id, p.name, p.price, p.compare_price as original_price, 
                       COALESCE(p.main_image, pi.image_path) as main_image, 
                       c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.status = 'active' AND (
                    p.name LIKE ? OR 
                    p.short_description LIKE ?
                )
                ORDER BY 
                    CASE 
                        WHEN p.name LIKE ? THEN 1
                        WHEN p.name LIKE ? THEN 2
                        ELSE 3
                    END,
                    p.is_featured DESC,
                    p.created_at DESC 
                LIMIT ?";
        
        $exactMatch = "$query%";
        $partialMatch = "%$query%";
        $startsWith = "$query%";
        $contains = "%$query%";
        
        $params = [$partialMatch, $partialMatch, $exactMatch, $startsWith, $limit];
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar resultados de búsqueda total
     */
    public function countSearchResults($query) {
        $sql = "SELECT COUNT(*) as total
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.status = 'active' AND (
                    p.name LIKE ? OR 
                    p.description LIKE ? OR 
                    p.short_description LIKE ? OR
                    b.name LIKE ? OR
                    c.name LIKE ?
                )";
        
        $searchTerm = "%$query%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    }
}
