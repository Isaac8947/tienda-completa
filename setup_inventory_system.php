<?php
// Setup completo del sistema de inventario y notificaciones
try {
    $pdo = new PDO('mysql:host=localhost;dbname=odisea_makeup', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado a la base de datos exitosamente\n";
    
    // 1. Crear tabla de historial de inventario
    $sql_inventory = "CREATE TABLE IF NOT EXISTS inventory_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        order_id INT NULL,
        movement_type ENUM('sale', 'restock', 'adjustment', 'return') NOT NULL,
        quantity_change INT NOT NULL,
        quantity_before INT NOT NULL,
        quantity_after INT NOT NULL,
        reason TEXT,
        created_by_user_id INT,
        created_by_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product_id (product_id),
        INDEX idx_order_id (order_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_inventory);
    echo "✓ Tabla inventory_history creada exitosamente\n";
    
    // 2. Crear tabla de notificaciones para admin
    $sql_notifications = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('order', 'stock', 'user', 'system') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        related_id INT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_type (type),
        INDEX idx_is_read (is_read),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_notifications);
    echo "✓ Tabla admin_notifications creada exitosamente\n";
    
    // 3. Verificar si la columna stock existe en productos
    $check_stock = $pdo->query("DESCRIBE products");
    $columns = $check_stock->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('stock', $columns)) {
        $sql_add_stock = "ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price";
        $pdo->exec($sql_add_stock);
        echo "✓ Columna stock agregada a la tabla products\n";
    } else {
        echo "✓ Columna stock ya existe en la tabla products\n";
    }
    
    // 4. Verificar si la columna min_stock existe
    if (!in_array('min_stock', $columns)) {
        $sql_add_min_stock = "ALTER TABLE products ADD COLUMN min_stock INT DEFAULT 5 AFTER stock";
        $pdo->exec($sql_add_min_stock);
        echo "✓ Columna min_stock agregada a la tabla products\n";
    } else {
        echo "✓ Columna min_stock ya existe en la tabla products\n";
    }
    
    // 5. Crear datos de ejemplo para notificaciones
    $sample_notifications = [
        [
            'type' => 'system',
            'title' => 'Sistema de inventario activado',
            'message' => 'El sistema de gestión de inventario ha sido configurado correctamente.',
            'priority' => 'medium'
        ],
        [
            'type' => 'order',
            'title' => 'Pedidos pendientes',
            'message' => 'Hay pedidos pendientes de revisión en el sistema.',
            'priority' => 'high'
        ]
    ];
    
    $insert_notification = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, priority) VALUES (?, ?, ?, ?)");
    
    foreach ($sample_notifications as $notification) {
        $insert_notification->execute([
            $notification['type'],
            $notification['title'],
            $notification['message'],
            $notification['priority']
        ]);
    }
    
    echo "✓ Notificaciones de ejemplo creadas\n";
    
    echo "\n=== CONFIGURACIÓN COMPLETADA ===\n";
    echo "El sistema de inventario y notificaciones está listo.\n";
    echo "Tablas creadas: inventory_history, admin_notifications\n";
    echo "Columnas agregadas: stock, min_stock en products\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
