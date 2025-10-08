<?php
require_once __DIR__ . '/BaseModel.php';

class Admin extends BaseModel {
    protected $table = 'admins';
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden = ['password'];
    
    public function login($email, $password) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Actualizar último login
            $this->update($admin['id'], ['last_login' => date('Y-m-d H:i:s')]);
            
            // Log de actividad
            $this->logActivity($admin['id'], 'login', 'Inicio de sesión exitoso');
            
            return $admin;
        }
        
        return false;
    }
    
    public function logout($adminId) {
        $this->logActivity($adminId, 'logout', 'Cierre de sesión');
        return true;
    }
    
    public function createAdmin($data) {
        // Hash de la contraseña
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    public function updateProfile($id, $data) {
        // Si se está actualizando la contraseña, hashearla
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        return $this->update($id, $data);
    }
    
    public function getTotalOrders() {
        try {
            $sql = "SELECT COUNT(*) as total FROM orders";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getTotalCustomers() {
        try {
            $sql = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getTotalProducts() {
        try {
            $sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getMonthlyRevenue() {
        try {
            $sql = "SELECT SUM(total) as revenue FROM orders 
                    WHERE status IN ('processing', 'shipped', 'delivered') 
                    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                    AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['revenue'] ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getPendingOrders() {
        try {
            $sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            // Return 0 if table doesn't exist or other error
            return 0;
        }
    }
    
    public function getLowStockProducts() {
        try {
            $sql = "SELECT COUNT(*) as total FROM products 
                    WHERE status = 'active' AND stock_quantity <= 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            // Return 0 if table doesn't exist or other error
            return 0;
        }
    }
    
    public function getRecentOrders($limit = 10) {
        try {
            $sql = "SELECT o.*, c.first_name, c.last_name 
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id 
                    ORDER BY o.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getTopProducts($limit = 5) {
        try {
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
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getSalesData($days = 7) {
        try {
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
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function logActivity($adminId, $action, $description, $entityType = null, $entityId = null) {
        $sql = "INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent, created_at) 
            VALUES ('admin', ?, ?, ?, ?, ?, NOW())";
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$adminId, $action, $description, $ipAddress, $userAgent]);
    }
    
    public function getActivityLogs($adminId = null, $limit = 50) {
    $sql = "SELECT al.*, a.full_name as admin_name 
        FROM activity_logs al 
        LEFT JOIN admins a ON al.user_id = a.id AND al.user_type = 'admin'";
        
        $params = [];
        
        if ($adminId) {
            $sql .= " WHERE al.user_id = ? AND al.user_type = 'admin'";
            $params[] = $adminId;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
