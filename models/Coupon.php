<?php
require_once __DIR__ . '/BaseModel.php';

class Coupon extends BaseModel {
    protected $table = 'coupons';
    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'minimum_amount',
        'maximum_discount', 'usage_limit', 'usage_limit_per_customer', 
        'used_count', 'start_date', 'end_date', 'status', 'customer_ids',
        'product_ids', 'category_ids', 'brand_ids', 'exclude_sale_items',
        'free_shipping', 'created_by_admin_id'
    ];
    
    public function getAllWithDetails($limit = 50, $offset = 0, $filters = []) {
        $sql = "SELECT c.*, 
                       a.full_name as created_by_admin,
                       (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id = c.id) as total_uses,
                       CASE
                           WHEN c.end_date < NOW() THEN 'expired'
                           WHEN c.start_date > NOW() THEN 'scheduled'
                           WHEN c.status = 'active' THEN 'active'
                           ELSE c.status
                       END as current_status
                FROM {$this->table} c
                LEFT JOIN admins a ON c.created_by_admin_id = a.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $sql .= " AND c.end_date < NOW()";
            } elseif ($filters['status'] === 'active') {
                $sql .= " AND c.status = 'active' AND c.start_date <= NOW() AND (c.end_date IS NULL OR c.end_date > NOW())";
            } elseif ($filters['status'] === 'scheduled') {
                $sql .= " AND c.start_date > NOW()";
            } else {
                $sql .= " AND c.status = ?";
                $params[] = $filters['status'];
            }
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND c.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.code LIKE ? OR c.name LIKE ? OR c.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['customer_specific'])) {
            if ($filters['customer_specific'] === '1') {
                $sql .= " AND c.customer_ids IS NOT NULL AND c.customer_ids != ''";
            } else {
                $sql .= " AND (c.customer_ids IS NULL OR c.customer_ids = '')";
            }
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
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
        $sql = "SELECT COUNT(*) as total FROM {$this->table} c WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $sql .= " AND c.end_date < NOW()";
            } elseif ($filters['status'] === 'active') {
                $sql .= " AND c.status = 'active' AND c.start_date <= NOW() AND (c.end_date IS NULL OR c.end_date > NOW())";
            } elseif ($filters['status'] === 'scheduled') {
                $sql .= " AND c.start_date > NOW()";
            } else {
                $sql .= " AND c.status = ?";
                $params[] = $filters['status'];
            }
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND c.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.code LIKE ? OR c.name LIKE ? OR c.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['customer_specific'])) {
            if ($filters['customer_specific'] === '1') {
                $sql .= " AND c.customer_ids IS NOT NULL AND c.customer_ids != ''";
            } else {
                $sql .= " AND (c.customer_ids IS NULL OR c.customer_ids = '')";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_coupons,
                    COUNT(CASE WHEN status = 'active' AND start_date <= NOW() AND (end_date IS NULL OR end_date > NOW()) THEN 1 END) as active_coupons,
                    COUNT(CASE WHEN end_date < NOW() THEN 1 END) as expired_coupons,
                    COUNT(CASE WHEN start_date > NOW() THEN 1 END) as scheduled_coupons,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_coupons,
                    COUNT(CASE WHEN customer_ids IS NOT NULL AND customer_ids != '' THEN 1 END) as customer_specific_coupons,
                    (SELECT COUNT(*) FROM coupon_usage) as total_usage,
                    (SELECT SUM(discount_amount) FROM coupon_usage) as total_discount_given
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function validateCoupon($code, $customerId = null, $orderAmount = 0, $cartItems = []) {
        $sql = "SELECT * FROM {$this->table} WHERE code = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Cupón no encontrado o inactivo'];
        }
        
        // Check date validity
        $now = date('Y-m-d H:i:s');
        if ($coupon['start_date'] > $now) {
            return ['valid' => false, 'message' => 'El cupón aún no está disponible'];
        }
        
        if ($coupon['end_date'] && $coupon['end_date'] < $now) {
            return ['valid' => false, 'message' => 'El cupón ha expirado'];
        }
        
        // Check usage limits
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'message' => 'El cupón ha alcanzado su límite de uso'];
        }
        
        // Check customer-specific restrictions
        if ($coupon['customer_ids']) {
            $allowedCustomers = json_decode($coupon['customer_ids'], true);
            if (!in_array($customerId, $allowedCustomers)) {
                return ['valid' => false, 'message' => 'Este cupón no está disponible para tu cuenta'];
            }
        }
        
        // Check per-customer usage limit
        if ($customerId && $coupon['usage_limit_per_customer']) {
            $sql = "SELECT COUNT(*) as customer_uses FROM coupon_usage WHERE coupon_id = ? AND customer_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$coupon['id'], $customerId]);
            $customerUses = $stmt->fetch()['customer_uses'];
            
            if ($customerUses >= $coupon['usage_limit_per_customer']) {
                return ['valid' => false, 'message' => 'Has alcanzado el límite de uso de este cupón'];
            }
        }
        
        // Check minimum amount
        if ($coupon['minimum_amount'] && $orderAmount < $coupon['minimum_amount']) {
            return ['valid' => false, 'message' => "Monto mínimo requerido: $" . number_format($coupon['minimum_amount'], 2)];
        }
        
        // Calculate discount
        $discount = $this->calculateDiscount($coupon, $orderAmount, $cartItems);
        
        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'message' => 'Cupón válido'
        ];
    }
    
    private function calculateDiscount($coupon, $orderAmount, $cartItems = []) {
        $discount = 0;
        
        if ($coupon['type'] === 'percentage') {
            $discount = ($orderAmount * $coupon['value']) / 100;
            
            // Apply maximum discount limit if set
            if ($coupon['maximum_discount'] && $discount > $coupon['maximum_discount']) {
                $discount = $coupon['maximum_discount'];
            }
        } elseif ($coupon['type'] === 'fixed') {
            $discount = min($coupon['value'], $orderAmount);
        }
        
        return round($discount, 2);
    }
    
    public function applyCoupon($couponId, $customerId, $orderId, $discountAmount) {
        try {
            $this->db->beginTransaction();
            
            // Record usage
            $sql = "INSERT INTO coupon_usage (coupon_id, customer_id, order_id, discount_amount, used_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$couponId, $customerId, $orderId, $discountAmount]);
            
            // Update coupon usage count
            $sql = "UPDATE {$this->table} SET used_count = used_count + 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$couponId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getCouponUsage($couponId, $limit = 50, $offset = 0) {
        $sql = "SELECT cu.*, 
                       c.first_name, c.last_name, c.email,
                       o.order_number, o.total
                FROM coupon_usage cu
                LEFT JOIN customers c ON cu.customer_id = c.id
                LEFT JOIN orders o ON cu.order_id = o.id
                WHERE cu.coupon_id = ?
                ORDER BY cu.used_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$couponId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getCustomerCoupons($customerId) {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id = c.id AND cu.customer_id = ?) as customer_uses
                FROM {$this->table} c
                WHERE c.status = 'active' 
                AND c.start_date <= NOW() 
                AND (c.end_date IS NULL OR c.end_date > NOW())
                AND (c.customer_ids IS NULL OR c.customer_ids = '' OR JSON_CONTAINS(c.customer_ids, ?))
                AND (c.usage_limit IS NULL OR c.used_count < c.usage_limit)
                ORDER BY c.value DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, json_encode($customerId)]);
        return $stmt->fetchAll();
    }
    
    public function generateUniqueCode($length = 8) {
        do {
            $code = '';
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Check if code already exists
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetch();
            
        } while ($exists);
        
        return $code;
    }
    
    public function bulkUpdateStatus($couponIds, $status) {
        if (empty($couponIds) || !in_array($status, ['active', 'inactive'])) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($couponIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $couponIds);
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function bulkDelete($couponIds) {
        if (empty($couponIds)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($couponIds) - 1) . '?';
            
            // Delete usage records first
            $sql = "DELETE FROM coupon_usage WHERE coupon_id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($couponIds);
            
            // Delete coupons
            $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($couponIds);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function duplicateCoupon($couponId) {
        $original = $this->findById($couponId);
        if (!$original) {
            return false;
        }
        
        // Remove id and generate new code
        unset($original['id']);
        $original['code'] = $this->generateUniqueCode();
        $original['name'] = $original['name'] . ' (Copia)';
        $original['used_count'] = 0;
        $original['created_at'] = date('Y-m-d H:i:s');
        $original['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($original);
    }
    
    public function getUpcomingExpirations($days = 7) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'active' 
                AND end_date IS NOT NULL 
                AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                ORDER BY end_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
?>
