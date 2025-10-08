<?php
require_once __DIR__ . '/BaseModel.php';

class Review extends BaseModel {
    protected $table = 'reviews';
    protected $fillable = [
        'product_id', 'customer_id', 'order_id', 
        'rating', 'title', 'comment', 'is_verified', 'is_approved',
        'helpful_count'
    ];
    
    public function getAllWithDetails($limit = 20, $offset = 0, $filters = []) {
        $sql = "SELECT r.*, 
                       p.name as product_name, 
                       p.main_image as product_image,
                       p.slug as product_slug,
                       c.first_name, c.last_name, c.email as customer_email_full
                FROM {$this->table} r
                LEFT JOIN products p ON r.product_id = p.id
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND r.is_approved = ?";
            $params[] = $filters['status'] === 'approved' ? 1 : 0;
        }
        
        if (!empty($filters['rating'])) {
            $sql .= " AND r.rating = ?";
            $params[] = $filters['rating'];
        }
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND r.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.comment LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR p.name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['verified_purchase'])) {
            $sql .= " AND r.is_verified = ?";
            $params[] = $filters['verified_purchase'];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function countWithFilters($filters = []) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} r
                LEFT JOIN products p ON r.product_id = p.id
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply same filters as getAllWithDetails
        if (!empty($filters['status'])) {
            $sql .= " AND r.is_approved = ?";
            $params[] = $filters['status'] === 'approved' ? 1 : 0;
        }
        
        if (!empty($filters['rating'])) {
            $sql .= " AND r.rating = ?";
            $params[] = $filters['rating'];
        }
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND r.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.comment LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR p.name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['verified_purchase'])) {
            $sql .= " AND r.is_verified = ?";
            $params[] = $filters['verified_purchase'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getByProductId($productId, $limit = 10, $offset = 0, $approved = true) {
        $sql = "SELECT r.*, 
                       c.first_name, c.last_name, c.email as customer_email_full
                FROM {$this->table} r
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE r.product_id = ?";
        
        $params = [$productId];
        
        if ($approved) {
            $sql .= " AND r.is_approved = 1";
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAverageRating($productId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM {$this->table} 
                WHERE product_id = ? AND is_approved = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        
        return [
            'average' => round($result['avg_rating'], 1),
            'count' => $result['total_reviews']
        ];
    }
    
    public function getRatingDistribution($productId) {
        $sql = "SELECT rating, COUNT(*) as count 
                FROM {$this->table} 
                WHERE product_id = ? AND is_approved = 1 
                GROUP BY rating 
                ORDER BY rating DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $results = $stmt->fetchAll();
        
        // Initialize all ratings to 0
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        
        foreach ($results as $result) {
            $distribution[$result['rating']] = $result['count'];
        }
        
        return $distribution;
    }
    
    public function canCustomerReview($customerId, $productId) {
        // Check if customer has purchased this product
        $sql = "SELECT COUNT(*) as count 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.customer_id = ? AND oi.product_id = ? AND o.status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $productId]);
        $purchaseResult = $stmt->fetch();
        
        if ($purchaseResult['count'] == 0) {
            return false; // Haven't purchased
        }
        
        // Check if customer has already reviewed this product
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE customer_id = ? AND product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $productId]);
        $reviewResult = $stmt->fetch();
        
        return $reviewResult['count'] == 0; // Can review if no existing review
    }
    
    public function createReview($data) {
        // Verify customer can review this product
        if (!$this->canCustomerReview($data['customer_id'], $data['product_id'])) {
            return false;
        }
        
        // Set is_verified based on purchase
        $data['is_verified'] = 1;
        $data['is_approved'] = 0; // Reviews need approval by default
        
        return $this->create($data);
    }
    
    public function approveReview($id) {
        return $this->update($id, ['is_approved' => 1]);
    }
    
    public function rejectReview($id) {
        return $this->update($id, ['is_approved' => 0]);
    }
    
    public function incrementHelpful($id) {
        $sql = "UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    // Admin methods
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_reviews, 
                    COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_reviews, 
                    COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_reviews,
                    AVG(rating) as average_rating, 
                    COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_reviews 
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getGlobalRatingDistribution() {
        $sql = "SELECT rating, COUNT(*) as count, 
                       (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {$this->table} WHERE is_approved = 1)) as percentage 
                FROM {$this->table} 
                WHERE is_approved = 1 
                GROUP BY rating 
                ORDER BY rating DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $approved = ($status === 'approved') ? 1 : 0;
        return $this->update($id, ['is_approved' => $approved]);
    }
    
    public function bulkUpdateStatus($reviewIds, $status) {
        if (empty($reviewIds)) {
            return false;
        }
        
        $approved = ($status === 'approved') ? 1 : 0;
        $placeholders = str_repeat('?,', count($reviewIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET is_approved = ? WHERE id IN ($placeholders)";
        $params = array_merge([$approved], $reviewIds);
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function bulkDelete($reviewIds) {
        if (empty($reviewIds)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($reviewIds) - 1) . '?';
        $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($reviewIds);
    }
    
    public function getRecentReviews($limit = 5) {
        $sql = "SELECT r.*, p.name as product_name, p.main_image as product_image 
                FROM {$this->table} r 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE r.is_approved = 1 
                ORDER BY r.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>
