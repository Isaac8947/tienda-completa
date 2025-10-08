<?php
require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel {
    protected $table = 'orders';
    protected $fillable = [
        'order_number', 'customer_id', 'customer_email', 'status', 'payment_status',
        'payment_method', 'payment_reference', 'subtotal', 'discount_amount',
        'tax_amount', 'shipping_amount', 'total', 'currency', 'billing_address',
        'shipping_address', 'shipping_method', 'tracking_number', 'notes', 'admin_notes'
    ];
    
    public function getAll($orderBy = 'created_at DESC') {
        return $this->findAll([], $orderBy);
    }
    
    public function getByStatus($status, $orderBy = 'created_at DESC') {
        return $this->findAll(['status' => $status], $orderBy);
    }
    
    public function getByCustomer($customerId, $orderBy = 'created_at DESC') {
        return $this->findAll(['customer_id' => $customerId], $orderBy);
    }
    
    public function createFromCart($cartId, $customerData, $shippingData, $paymentData) {
        require_once __DIR__ . '/Cart.php';
        $cartModel = new Cart();
        
        $cart = $cartModel->findById($cartId);
        $cartItems = $cartModel->getCartItems($cartId);
        
        if (!$cart || empty($cartItems)) {
            throw new Exception('Carrito vacío o no encontrado');
        }
        
        $this->beginTransaction();
        
        try {
            // Crear orden
            $orderData = [
                'order_number' => generateOrderNumber(),
                'customer_id' => $customerData['customer_id'] ?? null,
                'customer_email' => $customerData['email'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $paymentData['method'],
                'subtotal' => $cart['subtotal'],
                'discount_amount' => $cart['discount_amount'],
                'tax_amount' => $cart['tax_amount'],
                'shipping_amount' => $cart['shipping_amount'],
                'total' => $cart['total'],
                'billing_address' => json_encode($customerData['billing_address']),
                'shipping_address' => json_encode($shippingData['shipping_address']),
                'shipping_method' => $shippingData['method'],
                'notes' => $customerData['notes'] ?? ''
            ];
            
            $orderId = $this->create($orderData);
            
            // Crear items de la orden
            foreach ($cartItems as $item) {
                $this->createOrderItem($orderId, $item);
                
                // Actualizar inventario
                require_once __DIR__ . '/Product.php';
                $productModel = new Product();
                $productModel->updateInventory($item['product_id'], $item['quantity']);
            }
            
            // Limpiar carrito
            $cartModel->clearCart($cartId);
            
            $this->commit();
            
            // Log de actividad
            if ($customerData['customer_id']) {
                logActivity('customer', $customerData['customer_id'], 'order_created', "Orden #{$orderData['order_number']} creada");
            }
            
            return $orderId;
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    private function createOrderItem($orderId, $cartItem) {
        $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        $total = $cartItem['quantity'] * $cartItem['price'];
        
        return $stmt->execute([
            $orderId,
            $cartItem['product_id'],
            $cartItem['product_name'],
            $cartItem['quantity'],
            $cartItem['price'],
            $total
        ]);
    }
    
    public function getOrderWithItems($id) {
        $order = $this->findById($id);
        if ($order) {
            $order['items'] = $this->getOrderItems($id);
            $order['billing_address'] = json_decode($order['billing_address'], true);
            $order['shipping_address'] = json_decode($order['shipping_address'], true);
        }
        return $order;
    }
    
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.main_image, p.slug as product_slug
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.created_at";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status, $adminNotes = '') {
        try {
            $this->db->beginTransaction();
            
            // Obtener estado actual
            $currentOrder = $this->findById($id);
            if (!$currentOrder) {
                throw new Exception('Orden no encontrada');
            }
            
            $oldStatus = $currentOrder['status'];
            
            // Si se cancela una orden que no estaba cancelada, restaurar stock
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->restoreStock($id);
            }
            
            // Si se reactiva una orden cancelada, reducir stock nuevamente
            if ($oldStatus === 'cancelled' && $status !== 'cancelled') {
                $this->reduceStock($id);
            }
            
            // Preparar datos para actualizar
            $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($adminNotes) {
                $data['admin_notes'] = $adminNotes;
            }
            
            // Actualizar orden
            $result = $this->update($id, $data);
            
            if ($result) {
                // Log de actividad
                $admin = getCurrentAdmin();
                if ($admin) {
                    logActivity('admin', $admin['id'], 'order_status_updated', "Orden #$id actualizada de $oldStatus a $status");
                }
                
                // Log del cambio de estado
                $this->logStatusChange($id, $oldStatus, $status, $admin['id'] ?? null);
            }
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Error updating order status: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function getCustomerOrders($customerId, $limit = 10, $offset = 0) {
        return $this->findAll(
            ['customer_id' => $customerId],
            'created_at DESC',
            $limit,
            $offset
        );
    }
    
    public function getOrdersByStatus($status, $limit = 20, $offset = 0) {
        return $this->findAll(
            ['status' => $status],
            'created_at DESC',
            $limit,
            $offset
        );
    }
    
    public function getRecentOrders($limit = 10) {
        return $this->findAll([], 'created_at DESC', $limit);
    }
    
    // Métodos para facturas/invoices
    public function generateInvoiceNumber($orderId) {
        $order = $this->getById($orderId);
        if (!$order) {
            return null;
        }
        
        // Generar número de factura: INV-YYYY-NNNN
        $year = date('Y', strtotime($order['created_at']));
        $invoiceNumber = 'INV-' . $year . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
        
        return $invoiceNumber;
    }
    
    public function getInvoiceData($orderId) {
        $order = $this->getOrderWithItems($orderId);
        if (!$order) {
            return null;
        }
        
        require_once __DIR__ . '/Customer.php';
        $customerModel = new Customer();
        $customer = $customerModel->getById($order['customer_id']);
        
        // Datos de la empresa (esto debería venir de configuración)
        $companyData = [
            'name' => 'Odisea Makeup',
            'address' => 'Calle 123 #45-67',
            'city' => 'Barranquilla, Colombia',
            'phone' => '+57 300 123 4567',
            'email' => 'info@odiseamakeup.com',
            'website' => 'www.odiseamakeup.com',
            'tax_id' => 'TAX123456789'
        ];
        
        return [
            'invoice_number' => $this->generateInvoiceNumber($orderId),
            'order' => $order,
            'customer' => $customer,
            'company' => $companyData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getOrdersByCustomerId($customerId) {
        return $this->getByCustomer($customerId);
    }
    
    /**
     * Crear orden desde array de carrito de sesión
     */
    public function createFromSessionCart($cartItems, $customerData, $addressData, $notes = '') {
        $transactionStarted = false;
        
        try {
            // Verificar que la conexión existe
            if (!$this->db) {
                throw new Exception('Conexión a base de datos no disponible');
            }
            
            // Iniciar transacción
            $transactionStarted = $this->db->beginTransaction();
            if (!$transactionStarted) {
                throw new Exception('No se pudo iniciar la transacción');
            }
            
            error_log('Transacción iniciada correctamente');
            
            // Calcular totales
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            $taxRate = 0.19; // 19% IVA
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $subtotal > 150000 ? 0 : 15000;
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;
            
            // Generar número de orden único
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Preparar direcciones como JSON
            $billingAddress = json_encode([
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'department' => $addressData['department'],
                'city' => $addressData['city'],
                'address' => $addressData['address']
            ]);
            
            $shippingAddress = json_encode($addressData);
            
            // Crear la orden
            $sql = "INSERT INTO orders (order_number, status, payment_method, payment_status, 
                                      subtotal, tax_amount, shipping_amount, total, 
                                      notes, billing_address, shipping_address, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $orderNumber,
                'pending',
                'cash_on_delivery',
                'pending',
                $subtotal,
                $taxAmount,
                $shippingAmount,
                $totalAmount,
                $notes,
                $billingAddress,
                $shippingAddress,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Crear los items de la orden y reducir stock
            require_once __DIR__ . '/../includes/InventoryManager.php';
            $inventoryManager = new InventoryManager($this->db);
            
            foreach ($cartItems as $item) {
                // Verificar stock disponible antes de procesar
                $productSql = "SELECT stock, name FROM products WHERE id = ? AND status = 'active'";
                $productStmt = $this->db->prepare($productSql);
                $productStmt->execute([$item['product_id']]);
                $product = $productStmt->fetch();
                
                if (!$product) {
                    throw new Exception("Producto ID {$item['product_id']} no encontrado");
                }
                
                if ($product['stock'] < $item['quantity']) {
                    throw new Exception("Stock insuficiente para {$product['name']}. Disponible: {$product['stock']}, Solicitado: {$item['quantity']}");
                }
                
                // Crear item de la orden
                $itemSql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, 
                                                   quantity, price, total, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    'SKU-' . $item['product_id'], // SKU temporal
                    $item['quantity'],
                    $item['price'],
                    $item['total'],
                    date('Y-m-d H:i:s')
                ]);
                
                // Registrar movimiento de inventario (que también actualiza el stock)
                try {
                    $inventoryManager->recordMovement(
                        $item['product_id'],           // product_id
                        $orderId,                      // order_id (reference_id)
                        'sale',                        // movement_type
                        -$item['quantity'],            // quantity_change
                        "Venta - Orden #{$orderNumber}", // reason
                        'Sistema'                      // user_name
                    );
                } catch (Exception $e) {
                    error_log('Error registrando movimiento de inventario: ' . $e->getMessage());
                    // No detener el proceso si falla el registro del historial
                }
            }
            
            // Confirmar transacción
            if ($transactionStarted && $this->db->inTransaction()) {
                $this->db->commit();
                error_log("Transacción confirmada exitosamente, orden creada con ID: $orderId");
            } else if ($transactionStarted) {
                error_log("Advertencia: Transacción no estaba activa al intentar commit");
            }
            
            return $orderId;
            
        } catch (Exception $e) {
            // Revertir transacción si está activa
            if ($transactionStarted && $this->db && $this->db->inTransaction()) {
                try {
                    $this->db->rollback();
                    error_log('Transacción revertida debido a error: ' . $e->getMessage());
                } catch (Exception $rollbackError) {
                    error_log('Error al revertir transacción: ' . $rollbackError->getMessage());
                }
            }
            
            error_log('Error creating order from session cart: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new Exception('Error al crear la orden: ' . $e->getMessage());
        }
    }
    
    /**
     * Restaurar stock cuando se cancela una orden
     */
    private function restoreStock($orderId) {
        require_once __DIR__ . '/../includes/InventoryManager.php';
        $inventoryManager = new InventoryManager($this->db);
        
        // Obtener items de la orden
        $sql = "SELECT oi.product_id, oi.quantity, oi.product_name, p.name as current_name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            // Registrar movimiento (que también restaura el stock)
            try {
                $inventoryManager->recordMovement(
                    $item['product_id'],           // product_id
                    $orderId,                      // order_id
                    'return',                      // movement_type
                    $item['quantity'],             // quantity_change
                    "Cancelación de orden #{$orderId}", // reason
                    'Admin'                        // user_name
                );
            } catch (Exception $e) {
                error_log('Error registrando movimiento de inventario para cancelación: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Reducir stock cuando se reactiva una orden
     */
    private function reduceStock($orderId) {
        require_once __DIR__ . '/../includes/InventoryManager.php';
        $inventoryManager = new InventoryManager($this->db);
        
        // Obtener items de la orden
        $sql = "SELECT oi.product_id, oi.quantity, oi.product_name, p.name as current_name, p.stock 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            // Verificar stock disponible
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Stock insuficiente para {$item['current_name']}. Disponible: {$item['stock']}, Necesario: {$item['quantity']}");
            }
            
            // Registrar movimiento (que también reduce el stock)
            try {
                $inventoryManager->recordMovement(
                    $item['product_id'],           // product_id
                    $orderId,                      // order_id
                    'sale',                        // movement_type
                    -$item['quantity'],            // quantity_change
                    "Reactivación de orden #{$orderId}", // reason
                    'Admin'                        // user_name
                );
            } catch (Exception $e) {
                error_log('Error registrando movimiento de inventario para reactivación: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Registrar cambio de estado
     */
    private function logStatusChange($orderId, $oldStatus, $newStatus, $adminId = null) {
        try {
            $sql = "INSERT INTO order_status_logs (order_id, previous_status, new_status, changed_by, created_at) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $oldStatus, $newStatus, $adminId, date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            // Si la tabla no existe, continuar sin error
            error_log('Warning: Could not log status change: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todas las órdenes con datos para facturas
     */
    public function getAllWithInvoiceData($limit = 20, $offset = 0, $filters = []) {
        $sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id ";
        
        $whereClause = [];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereClause[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $whereClause[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener total de órdenes
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) FROM orders o ";
        
        $whereClause = [];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereClause[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $whereClause[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }

    /**
     * Obtener cantidad de órdenes por mes
     */
    public function getCountByMonth($year, $month) {
        $sql = "SELECT COUNT(*) FROM orders WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        
        return $stmt->fetchColumn();
    }
}
