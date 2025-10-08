<?php
require_once __DIR__ . '/BaseModel.php';

class Customer extends BaseModel {
    protected $table = 'customers';
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'phone', 'birth_date',
        'gender', 'is_active', 'email_verified', 'verification_token', 'reset_token', 'reset_expires'
    ];
    protected $hidden = ['password', 'verification_token', 'reset_token'];
    
    public function getAll($orderBy = 'created_at DESC') {
        return $this->findAll([], $orderBy);
    }
    
    public function getActive($orderBy = 'created_at DESC') {
        return $this->findAll(['is_active' => 1], $orderBy);
    }
    
    public function login($email, $password) {
        $customer = $this->findOne(['email' => $email, 'is_active' => 1]);
        
        if ($customer && password_verify($password, $customer['password'])) {
            // Actualizar último login
            $this->update($customer['id'], ['last_login' => date('Y-m-d H:i:s')]);
            
            // Log de actividad
            logActivity('customer', $customer['id'], 'login', 'Inicio de sesión');
            
            return $customer;
        }
        
        return false;
    }
    
    public function register($data) {
        // Verificar si el email ya existe
        if ($this->exists(['email' => $data['email']])) {
            throw new Exception('El email ya está registrado');
        }
        
        // Hashear contraseña
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generar token de verificación
        $data['verification_token'] = generateToken();
        $data['email_verified'] = 0;
        $data['is_active'] = 1;
        
        $customerId = $this->create($data);
        
        if ($customerId) {
            // Enviar email de verificación
            $this->sendVerificationEmail($data['email'], $data['verification_token']);
            
            // Log de actividad
            logActivity('customer', $customerId, 'register', 'Cuenta creada');
        }
        
        return $customerId;
    }
    
    public function verifyEmail($token) {
        $customer = $this->findOne(['verification_token' => $token]);
        
        if (!$customer) {
            throw new Exception('Token de verificación inválido');
        }
        
        return $this->update($customer['id'], [
            'email_verified' => 1,
            'verification_token' => null
        ]);
    }
    
    public function requestPasswordReset($email) {
        $customer = $this->findOne(['email' => $email, 'is_active' => 1]);
        
        if (!$customer) {
            throw new Exception('Email no encontrado');
        }
        
        $resetToken = generateToken();
        $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $result = $this->update($customer['id'], [
            'reset_token' => $resetToken,
            'reset_expires' => $resetExpires
        ]);
        
        if ($result) {
            $this->sendPasswordResetEmail($email, $resetToken);
        }
        
        return $result;
    }
    
    public function resetPassword($token, $newPassword) {
        $customer = $this->findOne([
            'reset_token' => $token,
            'is_active' => 1
        ]);
        
        if (!$customer) {
            throw new Exception('Token de restablecimiento inválido');
        }
        
        // Verificar que el token no haya expirado
        if (strtotime($customer['reset_expires']) < time()) {
            throw new Exception('Token de restablecimiento expirado');
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($customer['id'], [
            'password' => $hashedPassword,
            'reset_token' => null,
            'reset_expires' => null
        ]);
    }
    
    public function updateProfile($customerId, $data) {
        // Remover campos que no se pueden actualizar directamente
        unset($data['password']);
        unset($data['email']);
        unset($data['verification_token']);
        unset($data['reset_token']);
        
        $result = $this->update($customerId, $data);
        
        if ($result) {
            logActivity('customer', $customerId, 'profile_updated', 'Perfil actualizado');
        }
        
        return $result;
    }
    
    public function changePassword($customerId, $currentPassword, $newPassword) {
        $customer = $this->findById($customerId);
        
        if (!$customer || !password_verify($currentPassword, $customer['password'])) {
            throw new Exception('Contraseña actual incorrecta');
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $result = $this->update($customerId, ['password' => $hashedPassword]);
        
        if ($result) {
            logActivity('customer', $customerId, 'password_changed', 'Contraseña cambiada');
        }
        
        return $result;
    }
    
    private function sendVerificationEmail($email, $token) {
        $verificationUrl = BASE_URL . "/verify-email.php?token=$token";
        $subject = "Verifica tu cuenta en " . APP_NAME;
        $message = "Haz clic en el siguiente enlace para verificar tu cuenta: $verificationUrl";
        
        return sendEmail($email, $subject, $message);
    }
    
    private function sendPasswordResetEmail($email, $token) {
        $resetUrl = BASE_URL . "/reset-password.php?token=$token";
        $subject = "Restablece tu contraseña en " . APP_NAME;
        $message = "Haz clic en el siguiente enlace para restablecer tu contraseña: $resetUrl";
        
        return sendEmail($email, $subject, $message);
    }
    
    public function getCustomerByEmail($email) {
        return $this->findOne(['email' => $email]);
    }
    
    public function createCustomer($data) {
        // Verificar si el email ya existe
        if ($this->exists(['email' => $data['email']])) {
            return false;
        }
        
        // Ajustar nombres de campos si es necesario
        if (isset($data['status'])) {
            $data['is_active'] = $data['status'];
            unset($data['status']);
        }
        
        return $this->create($data);
    }
    
    public function getCustomerById($id) {
        return $this->findById($id);
    }
}
