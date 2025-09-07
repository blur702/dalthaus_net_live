<?php

declare(strict_types=1);

namespace CMS\Utils;

use Exception;

/**
 * Secure File Upload Handler
 * 
 * Provides secure file upload functionality with extensive validation,
 * virus scanning simulation, and image reprocessing capabilities.
 * 
 * @package CMS\Utils
 * @author  Security Auditor
 * @version 1.0.0
 */
class FileUpload
{
    /**
     * Configuration settings
     */
    private array $config;
    
    /**
     * Allowed MIME types for images
     */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp']
    ];
    
    /**
     * Maximum file size (25MB)
     */
    private const MAX_FILE_SIZE = 26214400;
    
    /**
     * Constructor
     * 
     * @param array $config Upload configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'upload_path' => __DIR__ . '/../../uploads/',
            'max_size' => self::MAX_FILE_SIZE,
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'reprocess_images' => true,
            'strip_metadata' => true,
            'randomize_filename' => true
        ], $config);
    }
    
    /**
     * Handle file upload with comprehensive security checks
     * 
     * @param array $file $_FILES array element
     * @return array Result with success status and filename or error
     */
    public function upload(array $file): array
    {
        try {
            // Step 1: Validate upload error
            $this->validateUploadError($file);
            
            // Step 2: Check if file was actually uploaded
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new Exception('File was not uploaded through POST.');
            }
            
            // Step 3: Validate file size
            $this->validateFileSize($file);
            
            // Step 4: Validate MIME type and extension
            $mimeType = $this->validateMimeType($file);
            $extension = $this->validateExtension($file, $mimeType);
            
            // Step 5: Scan file content for malicious patterns
            $this->scanFileContent($file['tmp_name']);
            
            // Step 6: Generate secure filename
            $filename = $this->generateSecureFilename($extension);
            $destination = $this->config['upload_path'] . $filename;
            
            // Step 7: Process image (reprocess to remove potential malicious code)
            if ($this->config['reprocess_images']) {
                $this->processImage($file['tmp_name'], $destination, $mimeType);
            } else {
                // Move file to destination
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception('Failed to move uploaded file.');
                }
            }
            
            // Step 8: Set proper file permissions
            chmod($destination, 0644);
            
            // Step 9: Final verification
            if (!file_exists($destination)) {
                throw new Exception('File upload verification failed.');
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => filesize($destination),
                'mime_type' => $mimeType
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate upload error
     * 
     * @param array $file File data
     * @throws Exception On upload error
     */
    private function validateUploadError(array $file): void
    {
        if (!isset($file['error'])) {
            throw new Exception('Invalid file upload data.');
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds maximum allowed size.');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File was only partially uploaded.');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file was uploaded.');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Missing temporary folder.');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Failed to write file to disk.');
            case UPLOAD_ERR_EXTENSION:
                throw new Exception('File upload stopped by extension.');
            default:
                throw new Exception('Unknown upload error.');
        }
    }
    
    /**
     * Validate file size
     * 
     * @param array $file File data
     * @throws Exception If file size exceeds limit
     */
    private function validateFileSize(array $file): void
    {
        if ($file['size'] > $this->config['max_size']) {
            $maxSizeMB = $this->config['max_size'] / 1048576;
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB.");
        }
        
        // Also check actual file size on disk
        $actualSize = filesize($file['tmp_name']);
        if ($actualSize > $this->config['max_size']) {
            throw new Exception('File size validation failed.');
        }
    }
    
    /**
     * Validate MIME type
     * 
     * @param array $file File data
     * @return string Validated MIME type
     * @throws Exception If MIME type is not allowed
     */
    private function validateMimeType(array $file): string
    {
        // Get MIME type using multiple methods for better security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Verify against allowed MIME types
        if (!array_key_exists($mimeType, self::ALLOWED_IMAGE_MIMES)) {
            throw new Exception('File type not allowed. Only images are permitted.');
        }
        
        // Additional check for images
        if (str_starts_with($mimeType, 'image/')) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('Invalid image file.');
            }
            
            // Verify MIME type matches image info
            $imageMime = $imageInfo['mime'] ?? '';
            if ($imageMime !== $mimeType) {
                throw new Exception('File type mismatch detected.');
            }
        }
        
        return $mimeType;
    }
    
    /**
     * Validate file extension
     * 
     * @param array $file File data
     * @param string $mimeType MIME type
     * @return string Valid extension
     * @throws Exception If extension is not allowed
     */
    private function validateExtension(array $file, string $mimeType): string
    {
        $filename = $file['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check if extension is in allowed list
        if (!in_array($extension, $this->config['allowed_types'])) {
            throw new Exception('File extension not allowed.');
        }
        
        // Verify extension matches MIME type
        $validExtensions = self::ALLOWED_IMAGE_MIMES[$mimeType] ?? [];
        if (!in_array($extension, $validExtensions)) {
            throw new Exception('File extension does not match file type.');
        }
        
        return $extension;
    }
    
    /**
     * Scan file content for malicious patterns
     * 
     * @param string $filepath Path to file
     * @throws Exception If malicious content detected
     */
    private function scanFileContent(string $filepath): void
    {
        // Check if this is an image file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        $isImage = strpos($mimeType, 'image/') === 0;
        
        if ($isImage) {
            // For images, only check the first 1KB for PHP signatures
            // This avoids false positives from binary image data
            $handle = fopen($filepath, 'rb');
            $header = fread($handle, 1024);
            fclose($handle);
            
            // Check for PHP tags in the header
            if (preg_match('/<\?php|<\?=/i', $header)) {
                throw new Exception('PHP code detected in image file.');
            }
        } else {
            // For non-image files, do full content scanning
            $content = file_get_contents($filepath);
            
            // Check for PHP tags
            if (preg_match('/<\?php|<\?=/i', $content)) {
                throw new Exception('Potentially malicious content detected.');
            }
            
            // Check for common web shells patterns
            $maliciousPatterns = [
                '/eval\s*\(/i',
                '/base64_decode\s*\(/i',
                '/shell_exec\s*\(/i',
                '/system\s*\(/i',
                '/passthru\s*\(/i',
                '/exec\s*\(/i',
                '/popen\s*\(/i',
                '/proc_open\s*\(/i',
                '/include\s*\(/i',
                '/require\s*\(/i',
                '/file_get_contents\s*\(/i',
                '/file_put_contents\s*\(/i',
                '/fopen\s*\(/i'
            ];
            
            foreach ($maliciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new Exception('Suspicious content pattern detected.');
                }
            }
            
            // Check for null bytes in non-image files
            if (strpos($content, chr(0)) !== false) {
                throw new Exception('Null byte detected in file.');
            }
        }
    }
    
    /**
     * Generate cryptographically secure random filename
     * 
     * @param string $extension File extension
     * @return string Secure filename
     */
    private function generateSecureFilename(string $extension): string
    {
        if ($this->config['randomize_filename']) {
            // Generate 32 character random filename
            $filename = bin2hex(random_bytes(16));
            return $filename . '.' . $extension;
        }
        
        // If not randomizing, still add random prefix for uniqueness
        $prefix = bin2hex(random_bytes(8));
        return $prefix . '_' . time() . '.' . $extension;
    }
    
    /**
     * Process and reprocess image to remove potential malicious code
     * 
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param string $mimeType MIME type
     * @throws Exception If image processing fails
     */
    private function processImage(string $source, string $destination, string $mimeType): void
    {
        // Load image based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($source);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($source);
                break;
            default:
                throw new Exception('Unsupported image type for processing.');
        }
        
        if ($image === false) {
            throw new Exception('Failed to process image file.');
        }
        
        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create new clean image
        $newImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG and WebP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }
        
        // Copy image data (this strips metadata and potential malicious code)
        imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
        
        // Save processed image
        $saved = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $saved = imagejpeg($newImage, $destination, 85);
                break;
            case 'image/png':
                $saved = imagepng($newImage, $destination, 9);
                break;
            case 'image/gif':
                $saved = imagegif($newImage, $destination);
                break;
            case 'image/webp':
                $saved = imagewebp($newImage, $destination, 85);
                break;
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($newImage);
        
        if (!$saved) {
            throw new Exception('Failed to save processed image.');
        }
    }
    
    /**
     * Delete uploaded file
     * 
     * @param string $filename Filename to delete
     * @return bool Success status
     */
    public function delete(string $filename): bool
    {
        $filepath = $this->config['upload_path'] . $filename;
        
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Get upload directory size
     * 
     * @return int Size in bytes
     */
    public function getDirectorySize(): int
    {
        $size = 0;
        $files = glob($this->config['upload_path'] . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }
        
        return $size;
    }
    
    /**
     * Clean old uploads (older than specified days)
     * 
     * @param int $days Number of days
     * @return int Number of files deleted
     */
    public function cleanOldUploads(int $days = 30): int
    {
        $deleted = 0;
        $threshold = time() - ($days * 86400);
        $files = glob($this->config['upload_path'] . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
