<?php
require_once __DIR__ . '/BaseModel.php';

class Settings extends BaseModel {
    protected $table = 'settings';
    protected $fillable = [
        'key_name', 'value', 'description', 'type', 'group_name'
    ];
    
    /**
     * Obtiene una configuración por su clave
     * 
     * @param string $key Clave de la configuración
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor de la configuración
     */
    public function get($key, $default = null) {
        $setting = $this->findOne(['key_name' => $key]);
        return $setting ? $setting['value'] : $default;
    }
    
    /**
     * Establece una configuración
     * 
     * @param string $key Clave de la configuración
     * @param mixed $value Valor de la configuración
     * @param string $description Descripción de la configuración
     * @param string $type Tipo de la configuración
     * @return bool
     */
    public function set($key, $value, $description = '', $type = 'text') {
        $existing = $this->findOne(['key_name' => $key]);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'value' => $value,
                'description' => $description ?: $existing['description'],
                'type' => $type ?: $existing['type']
            ]);
        } else {
            return $this->create([
                'key_name' => $key,
                'value' => $value,
                'description' => $description,
                'type' => $type
            ]);
        }
    }
    
    /**
     * Obtiene múltiples configuraciones
     * 
     * @param array $keys Claves de las configuraciones
     * @return array Array asociativo con las configuraciones
     */
    public function getMultiple($keys) {
        if (empty($keys)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
    $sql = "SELECT key_name, value FROM {$this->table} WHERE key_name IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($keys);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['key_name']] = $row['value'];
        }
        
        return $results;
    }
    
    /**
     * Obtiene todas las configuraciones de contacto y redes sociales
     * 
     * @return array Array con las configuraciones
     */
    public function getContactSettings() {
        $keys = [
            'site_phone',
            'site_email', 
            'social_facebook',
            'social_instagram',
            'social_twitter',
            'social_youtube',
            'social_tiktok'
        ];
        
        return $this->getMultiple($keys);
    }
    
    /**
     * Obtiene todas las configuraciones del sitio
     * 
     * @return array Array con todas las configuraciones
     */
    public function getAllSettings() {
    $sql = "SELECT key_name, value FROM {$this->table}";
        $stmt = $this->db->query($sql);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key_name']] = $row['value'];
        }
        
        return $settings;
    }
    
    /**
     * Obtiene configuraciones agrupadas por categoría
     * 
     * @param string $category Categoría (ej: 'social', 'payment', 'smtp')
     * @return array Array con las configuraciones de la categoría
     */
    public function getByCategory($category) {
    $sql = "SELECT key_name, value FROM {$this->table} WHERE key_name LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category . '_%']);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key_name']] = $row['value'];
        }
        
        return $settings;
    }
}
