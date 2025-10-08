<?php
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel {
    protected $table = 'categories';
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'parent_id', 'sort_order',
        'is_active', 'meta_title', 'meta_description'
    ];
    
    public function getAll($orderBy = 'sort_order ASC') {
        return $this->findAll([], $orderBy);
    }
    
    public function getActive($orderBy = 'sort_order ASC') {
        return $this->findAll(['is_active' => 1], $orderBy);
    }
    
    public function getActiveCategories($limit = null) {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMainCategories() {
        return $this->findAll(['is_active' => 1, 'parent_id' => null], 'sort_order ASC');
    }
    
    public function getSubcategories($parentId) {
        return $this->findAll(['is_active' => 1, 'parent_id' => $parentId], 'sort_order ASC');
    }
    
    public function getCategoryWithSubcategories($id) {
        $category = $this->findById($id);
        if ($category) {
            $category['subcategories'] = $this->getSubcategories($id);
            $category['product_count'] = $this->getProductCount($id);
        }
        return $category;
    }
    
    public function getCategoryTree() {
        $categories = $this->getMainCategories();
        foreach ($categories as &$category) {
            $category['subcategories'] = $this->getSubcategories($category['id']);
            $category['product_count'] = $this->getProductCount($category['id']);
        }
        return $categories;
    }
    
    public function getProductCount($categoryId) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE category_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getCategoryTreeWithIcons() {
        // Iconos por defecto para categorías principales
        $icons = [
            'Tecnología' => 'fa-laptop',
            'Hogar' => 'fa-home',
            'Moda' => 'fa-tshirt',
            'Belleza' => 'fa-spa',
            'Deportes' => 'fa-dumbbell'
        ];
        
        $sql = "SELECT * FROM categories WHERE is_active = 1 AND (parent_id IS NULL OR parent_id = 0) ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        foreach ($categories as &$category) {
            $category['icon'] = $icons[$category['name']] ?? 'fa-folder';
            $category['subcategories'] = $this->getSubcategories($category['id']);
            $category['product_count'] = $this->getProductCount($category['id']);
        }
        
        return $categories;
    }
    
    public function findBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    public function searchCategories($query, $limit = 5) {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                WHERE c.is_active = 1 AND (
                    c.name LIKE ? OR 
                    c.description LIKE ?
                )
                GROUP BY c.id
                ORDER BY c.name ASC 
                LIMIT ?";
        
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }
}
?>
