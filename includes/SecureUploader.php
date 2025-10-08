<?php
/**
 * Secure File Upload Class
 */
class SecureUploader {
    private static $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    private static $maxFileSize = 5242880; // 5MB
    private static $uploadDir = 'uploads/';
    
    public static function uploadFile($file, $subDir = '') {
        // Check if file was uploaded without errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error occurred'];
        }
        
        // Check file size
        if ($file['size'] > self::$maxFileSize) {
            return ['success' => false, 'error' => 'File too large'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, self::$allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Generate secure filename
        $extension = self::$allowedTypes[$mimeType];
        $filename = self::generateSecureFilename() . '.' . $extension;
        
        // Create upload directory if it does not exist
        $targetDir = self::$uploadDir . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            chmod($targetPath, 0644);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $targetPath
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
    }
    
    private static function generateSecureFilename() {
        return bin2hex(random_bytes(16)) . '_' . time();
    }
}
?>