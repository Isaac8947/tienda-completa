<?php
require_once 'BaseModel.php';

class Offer extends BaseModel {
    protected $table = 'offers';
    protected $fillable = [
        'title', 'description', 'discount_percentage', 'start_date', 'end_date', 
        'is_active', 'max_uses', 'current_uses', 'min_purchase_amount',
        'applicable_products', 'applicable_categories', 'applicable_brands',
        'banner_text', 'banner_color', 'priority'
    ];

    /**
     * Crear tabla de ofertas si no existe
     */
    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            max_uses INT DEFAULT NULL,
            current_uses INT DEFAULT 0,
            min_purchase_amount DECIMAL(10,2) DEFAULT 0,
            applicable_products TEXT COMMENT 'JSON array of product IDs',
            applicable_categories TEXT COMMENT 'JSON array of category IDs',
            applicable_brands TEXT COMMENT 'JSON array of brand IDs',
            banner_text VARCHAR(500),
            banner_color VARCHAR(7) DEFAULT '#ef4444',
            priority INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        return $this->db->exec($sql);
    }

    /**
     * Obtener ofertas activas
     */
    public function getActiveOffers() {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND start_date <= ? 
                AND end_date >= ?
                ORDER BY priority DESC, created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$now, $now]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener oferta principal (la más prioritaria)
     */
    public function getMainOffer() {
        $offers = $this->getActiveOffers();
        return !empty($offers) ? $offers[0] : null;
    }

    /**
     * Verificar si hay ofertas activas
     */
    public function hasActiveOffers() {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE is_active = 1 
                AND start_date <= ? 
                AND end_date >= ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$now, $now]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Obtener tiempo restante de la oferta principal
     */
    public function getMainOfferTimeLeft() {
        $mainOffer = $this->getMainOffer();
        if (!$mainOffer) {
            return null;
        }

        $endDate = new DateTime($mainOffer['end_date']);
        $now = new DateTime();
        
        if ($endDate <= $now) {
            return null;
        }

        $interval = $now->diff($endDate);
        
        return [
            'days' => $interval->days,
            'hours' => $interval->h,
            'minutes' => $interval->i,
            'seconds' => $interval->s,
            'total_seconds' => ($endDate->getTimestamp() - $now->getTimestamp()),
            'end_date' => $mainOffer['end_date'],
            'offer_data' => $mainOffer
        ];
    }

    /**
     * Incrementar el uso de una oferta
     */
    public function incrementUse($offerId) {
        $sql = "UPDATE {$this->table} SET current_uses = current_uses + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$offerId]);
    }

    /**
     * Verificar si una oferta ha alcanzado su límite de uso
     */
    public function hasReachedLimit($offerId) {
        $offer = $this->findById($offerId);
        if (!$offer || !$offer['max_uses']) {
            return false;
        }
        
        return $offer['current_uses'] >= $offer['max_uses'];
    }

    /**
     * Desactivar ofertas expiradas
     */
    public function deactivateExpiredOffers() {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE end_date < ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$now]);
    }

    /**
     * Obtener estadísticas de ofertas
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN end_date < NOW() THEN 1 ELSE 0 END) as expired,
                    SUM(current_uses) as total_uses,
                    AVG(discount_percentage) as avg_discount
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return [
            'total' => (int)$result['total'],
            'active' => (int)$result['active'],
            'inactive' => (int)$result['inactive'],
            'expired' => (int)$result['expired'],
            'total_uses' => (int)$result['total_uses'],
            'avg_discount' => round($result['avg_discount'], 2)
        ];
    }

    /**
     * Crear oferta de muestra
     */
    public function createSampleOffer() {
        $endDate = new DateTime();
        $endDate->add(new DateInterval('P7D')); // 7 días desde ahora
        
        $sampleOffer = [
            'title' => 'Mega Sale - Hasta 50% OFF',
            'description' => 'Aprovecha nuestras ofertas especiales por tiempo limitado. Descuentos de hasta 50% en productos seleccionados.',
            'discount_percentage' => 50.00,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'is_active' => 1,
            'max_uses' => null,
            'current_uses' => 0,
            'min_purchase_amount' => 0,
            'applicable_products' => null,
            'applicable_categories' => null,
            'applicable_brands' => null,
            'banner_text' => '🔥 ¡MEGA SALE! Hasta 50% OFF por tiempo limitado 🔥',
            'banner_color' => '#ef4444',
            'priority' => 1
        ];
        
        return $this->create($sampleOffer);
    }

    /**
     * Obtener productos en oferta
     */
    public function getOffersProducts($offerId = null) {
        if ($offerId) {
            $offer = $this->findById($offerId);
            if (!$offer) return [];
            
            // Si hay productos específicos definidos
            if ($offer['applicable_products']) {
                $productIds = json_decode($offer['applicable_products'], true);
                if (is_array($productIds) && !empty($productIds)) {
                    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                    $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($productIds);
                    return $stmt->fetchAll();
                }
            }
            
            // Si hay categorías específicas
            if ($offer['applicable_categories']) {
                $categoryIds = json_decode($offer['applicable_categories'], true);
                if (is_array($categoryIds) && !empty($categoryIds)) {
                    $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
                    $sql = "SELECT * FROM products WHERE category_id IN ($placeholders) AND status = 'active'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($categoryIds);
                    return $stmt->fetchAll();
                }
            }
            
            // Si hay marcas específicas
            if ($offer['applicable_brands']) {
                $brandIds = json_decode($offer['applicable_brands'], true);
                if (is_array($brandIds) && !empty($brandIds)) {
                    $placeholders = str_repeat('?,', count($brandIds) - 1) . '?';
                    $sql = "SELECT * FROM products WHERE brand_id IN ($placeholders) AND status = 'active'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($brandIds);
                    return $stmt->fetchAll();
                }
            }
        }
        
        // Si no hay restricciones específicas, retornar todos los productos activos
        $sql = "SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
