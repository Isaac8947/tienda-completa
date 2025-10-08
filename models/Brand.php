<?php
require_once __DIR__ . '/BaseModel.php';

class Brand extends BaseModel {
    protected $table = 'brands';
    protected $fillable = [
        'name', 'slug', 'description', 'logo', 'website', 'is_active'
    ];
    
    public function getAll($orderBy = 'name ASC') {
        return $this->findAll([], $orderBy);
    }
    
    public function getActive($orderBy = 'name ASC') {
        return $this->findAll(['is_active' => 1], $orderBy);
    }
    
    public function getActiveBrands() {
        return $this->findAll(['is_active' => 1], 'name ASC');
    }
    
    public function getBrandWithProducts($id) {
        $brand = $this->findById($id);
        if ($brand) {
            $brand['product_count'] = $this->getProductCount($id);
        }
        return $brand;
    }
    
    public function getProductCount($brandId) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE brand_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$brandId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getTopBrands($limit = 10) {
        $sql = "SELECT b.*, COUNT(p.id) as product_count
                FROM brands b
                LEFT JOIN products p ON b.id = p.brand_id AND p.status = 'active'
                WHERE b.is_active = 1
                GROUP BY b.id
                ORDER BY product_count DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function searchBrands($query, $limit = 5) {
        $sql = "SELECT b.*, COUNT(p.id) as product_count
                FROM brands b
                LEFT JOIN products p ON b.id = p.brand_id AND p.status = 'active'
                WHERE b.is_active = 1 AND (
                    b.name LIKE ? OR 
                    b.description LIKE ?
                )
                GROUP BY b.id
                ORDER BY b.name ASC 
                LIMIT ?";
        
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }
}
?>
