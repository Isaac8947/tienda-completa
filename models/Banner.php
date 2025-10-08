<?php
require_once __DIR__ . '/BaseModel.php';

class Banner extends BaseModel {
    protected $table = 'banners';
    protected $fillable = [
        'title', 'subtitle', 'description', 'link_url', 'link_text', 
        'image', 'position', 'sort_order', 'is_active', 'starts_at', 'expires_at'
    ];
    
    public function getAll($orderBy = 'sort_order ASC, id DESC') {
        return $this->findAll([], $orderBy);
    }
    
    public function getActive($orderBy = 'sort_order ASC, id DESC') {
        return $this->findAll(['is_active' => 1], $orderBy);
    }
    
    /**
     * Obtiene los banners activos por tipo
     * 
     * @param string $position Posición del banner (hero, category, product, footer)
     * @param int $limit Límite de banners a obtener
     * @return array Banners activos
     */
    public function getActiveBanners($position = 'hero', $limit = 3) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE position = ? AND is_active = 1 
                ORDER BY sort_order ASC, id DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$position, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene banners por posición
     * 
     * @param string $position Posición del banner
     * @param string $orderBy Ordenamiento
     * @return array Banners
     */
    public function getByPosition($position, $orderBy = 'sort_order ASC, id DESC') {
        return $this->findAll(['position' => $position], $orderBy);
    }
    
    /**
     * Obtiene un banner por su ID (método legacy)
     * 
     * @param int $id ID del banner
     * @return array|null Banner o null si no existe
     */
    public function getBannerById($id) {
        return $this->findById($id);
    }
}
?>
