<?php
require_once 'BaseModel.php';

class News extends BaseModel {
    protected $table = 'news';
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 
        'author_id', 'status', 'is_featured',
        'meta_title', 'meta_description', 'published_at'
    ];
    protected $hidden = ['deleted_at'];

    /**
     * Obtener todas las noticias con información del autor
     */
    public function getAllWithAuthor($status = null) {
        $sql = "SELECT n.*, a.full_name as author_name 
                FROM {$this->table} n 
                LEFT JOIN admins a ON n.author_id = a.id";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE n.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY n.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener noticias publicadas para el frontend
     */
    public function getPublished($limit = null, $featured = false) {
        $sql = "SELECT n.*, a.full_name as author_name 
                FROM {$this->table} n 
                LEFT JOIN admins a ON n.author_id = a.id 
                WHERE n.status = 'published' AND n.published_at <= NOW()";
        
        if ($featured) {
            $sql .= " AND n.is_featured = 1";
        }
        
        $sql .= " ORDER BY n.published_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limit) {
            $stmt->execute([$limit]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener una noticia por slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT n.*, a.full_name as author_name, a.email as author_email 
                FROM {$this->table} n 
                LEFT JOIN admins a ON n.author_id = a.id 
                WHERE n.slug = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        
        return $stmt->fetch();
    }

    /**
     * Generar slug único a partir del título
     */
    public function generateSlug($title, $id = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $id)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Verificar si un slug ya existe
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Incrementar las visualizaciones de una noticia
     * Nota: La columna views no existe en la tabla actual
     */
    public function incrementViews($id) {
        // Por ahora no hacemos nada ya que no existe la columna views
        // En el futuro se puede agregar esta funcionalidad
        return true;
    }

    /**
     * Obtener noticias relacionadas por categoría
     */
    public function getRelated($category, $excludeId, $limit = 3) {
        $sql = "SELECT n.*, a.full_name as author_name 
                FROM {$this->table} n 
                LEFT JOIN admins a ON n.author_id = a.id 
                WHERE n.category = ? AND n.id != ? AND n.status = 'published' 
                ORDER BY n.published_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category, $excludeId, $limit]);
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener estadísticas de noticias
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END), 0) as published,
                    COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END), 0) as drafts,
                    COALESCE(SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END), 0) as featured
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        // Asegurar que todos los valores sean enteros
        return [
            'total' => (int)$result['total'],
            'published' => (int)$result['published'],
            'drafts' => (int)$result['drafts'],
            'featured' => (int)$result['featured'],
            'total_views' => 0 // Por ahora 0 ya que no tenemos columna views
        ];
    }

    /**
     * Buscar noticias con filtros específicos
     */
    public function searchNews($term, $status = null, $category = null) {
        $sql = "SELECT n.*, a.full_name as author_name 
                FROM {$this->table} n 
                LEFT JOIN admins a ON n.author_id = a.id 
                WHERE (n.title LIKE ? OR n.content LIKE ? OR n.excerpt LIKE ?)";
        
        $params = ["%$term%", "%$term%", "%$term%"];
        
        if ($status) {
            $sql .= " AND n.status = ?";
            $params[] = $status;
        }
        
        if ($category) {
            $sql .= " AND n.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY n.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Método search compatible con BaseModel
     */
    public function search($query, $fields = [], $conditions = [], $limit = 20) {
        if (empty($fields)) {
            $fields = ['title', 'content', 'excerpt'];
        }
        
        return parent::search($query, $fields, $conditions, $limit);
    }

    /**
     * Obtener categorías con contadores
     */
    public function getCategoriesWithCount() {
        $sql = "SELECT 
                    category,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE status = 'published'
                GROUP BY category 
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>
