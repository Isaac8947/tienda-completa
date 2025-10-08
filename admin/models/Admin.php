<?php
require_once '../models/BaseModel.php';

class Admin extends BaseModel {
    protected $table = 'admins';
    
    public function getTotalOrders() {
        $sql = "SELECT COUNT(*) as total FROM orders";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getTotalCustomers() {
        $sql = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getTotalProducts() {
        $sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getMonthlyRevenue() {
        $sql = "SELECT SUM(total) as revenue FROM orders 
                WHERE status IN ('processing', 'shipped', 'delivered') 
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['revenue'] ?: 0;
    }
    
    public function getPendingOrders() {
        $sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getLowStockProducts() {
        $sql = "SELECT COUNT(*) as total FROM products 
                WHERE status = 'active' AND inventory_quantity <= 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getRecentOrders($limit = 10) {
        $sql = "SELECT o.*, c.first_name, c.last_name 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                ORDER BY o.created_at DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTopProducts($limit = 5) {
        $sql = "SELECT p.id, p.name, COUNT(oi.id) as sales_count, SUM(oi.total) as revenue
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('processing', 'shipped', 'delivered')
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.id, p.name
                ORDER BY sales_count DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getSalesData($days = 7) {
        $sql = "SELECT DATE(created_at) as date, SUM(total) as total_sales, COUNT(*) as order_count
                FROM orders 
                WHERE status IN ('processing', 'shipped', 'delivered')
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getProductsWithFilters($filters, $limit, $offset) {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = :brand";
            $params[':brand'] = $filters['brand'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['stock']) && $filters['stock'] === 'low') {
            $sql .= " AND p.inventory_quantity <= 5";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function countProductsWithFilters($filters) {
        $sql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand_id = :brand";
            $params[':brand'] = $filters['brand'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['stock']) && $filters['stock'] === 'low') {
            $sql .= " AND p.inventory_quantity <= 5";
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
}
?>
