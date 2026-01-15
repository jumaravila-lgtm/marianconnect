<?php
/**
 * MARIANCONNECT - FileUpload Class
 * Handles secure file uploads with validation, compression, and thumbnail generation
 */

class FileUpload {
    
    private $allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $allowedDocTypes = ['application/pdf'];
    private $maxFileSize = 5242880; // 5MB in bytes
    private $uploadBasePath;
    private $errors = [];
    
    /**
     * Constructor
     * 
     * @param string $basePath Base upload directory (default: assets/uploads)
     */
    public function __construct($basePath = null) {
        if ($basePath === null) {
            $this->uploadBasePath = $_SERVER['DOCUMENT_ROOT'] . '/marianconnect/assets/uploads';
        } else {
            $this->uploadBasePath = $basePath;
        }
    }
    
    /**
     * Upload an image file
     * 
     * @param array $file $_FILES array element
     * @param string $directory Subdirectory (e.g., 'news', 'events', 'gallery')
     * @param bool $createThumbnail Whether to create thumbnail
     * @param int $maxWidth Maximum width for image compression
     * @return array ['success' => bool, 'path' => string, 'thumbnail' => string, 'message' => string]
     */
    public function uploadImage($file, $directory, $createThumbnail = false, $maxWidth = 1920) {
        $this->errors = [];
        
        // Validate file upload
        if (!$this->validateUpload($file, 'image')) {
            return [
                'success' => false,
                'message' => implode(', ', $this->errors)
            ];
        }
        
        // Create directory if it doesn't exist
        $targetDir = $this->uploadBasePath . '/' . $directory;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;
        $relativePath = '/assets/uploads/' . $directory . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file'
            ];
        }
        
        // Compress image
        $this->compressImage($targetPath, $maxWidth);
        
        // Create thumbnail if requested
        $thumbnailPath = null;
        if ($createThumbnail) {
            $thumbnailPath = $this->createThumbnail($targetPath, $directory, $filename);
        }
        
        return [
            'success' => true,
            'path' => $relativePath,
            'thumbnail' => $thumbnailPath,
            'filename' => $filename,
            'message' => 'File uploaded successfully'
        ];
    }
    
    /**
     * Upload a PDF document
     * 
     * @param array $file $_FILES array element
     * @param string $directory Subdirectory
     * @return array ['success' => bool, 'path' => string, 'message' => string]
     */
    public function uploadDocument($file, $directory) {
        $this->errors = [];
        
        // Validate file upload
        if (!$this->validateUpload($file, 'document')) {
            return [
                'success' => false,
                'message' => implode(', ', $this->errors)
            ];
        }
        
        // Create directory if it doesn't exist
        $targetDir = $this->uploadBasePath . '/' . $directory;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;
        $relativePath = '/assets/uploads/' . $directory . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file'
            ];
        }
        
        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'message' => 'Document uploaded successfully'
        ];
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param string $type 'image' or 'document'
     * @return bool
     */
    private function validateUpload($file, $type = 'image') {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = 'No file uploaded or upload error occurred';
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = 'File size exceeds maximum allowed (' . $this->formatBytes($this->maxFileSize) . ')';
            return false;
        }
        
        // Check file type
        $fileType = mime_content_type($file['tmp_name']);
        
        if ($type === 'image') {
            if (!in_array($fileType, $this->allowedImageTypes)) {
                $this->errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed';
                return false;
            }
        } elseif ($type === 'document') {
            if (!in_array($fileType, $this->allowedDocTypes)) {
                $this->errors[] = 'Invalid file type. Only PDF documents are allowed';
                return false;
            }
        }
        
        // Check for malicious file names
        $filename = basename($file['name']);
        if (preg_match('/[^a-zA-Z0-9_\-\.]/', $filename)) {
            $this->errors[] = 'Invalid characters in filename';
            return false;
        }
        
        return true;
    }
    
    /**
     * Compress image to reduce file size
     * 
     * @param string $filePath Full path to image
     * @param int $maxWidth Maximum width in pixels
     * @return bool
     */
    private function compressImage($filePath, $maxWidth = 1920) {
        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Skip if image is already smaller than max width
        if ($width <= $maxWidth) {
            return true;
        }
        
        // Calculate new dimensions
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));
        
        // Create image resource based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return false;
        }
        
        if ($source === false) {
            return false;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save compressed image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $filePath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $filePath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($newImage, $filePath, 85);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($newImage);
        
        return true;
    }
    
    /**
     * Create thumbnail from image
     * 
     * @param string $sourcePath Full path to source image
     * @param string $directory Upload directory
     * @param string $filename Original filename
     * @param int $thumbWidth Thumbnail width
     * @param int $thumbHeight Thumbnail height
     * @return string|null Relative path to thumbnail or null on failure
     */
    private function createThumbnail($sourcePath, $directory, $filename, $thumbWidth = 300, $thumbHeight = 300) {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return null;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }
        
        if ($source === false) {
            return null;
        }
        
        // Calculate thumbnail dimensions (maintain aspect ratio)
        $aspectRatio = $width / $height;
        
        if ($width > $height) {
            $newWidth = $thumbWidth;
            $newHeight = floor($thumbWidth / $aspectRatio);
        } else {
            $newHeight = $thumbHeight;
            $newWidth = floor($thumbHeight * $aspectRatio);
        }
        
        // Create thumbnail
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save thumbnail
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath = $this->uploadBasePath . '/' . $directory . '/' . $thumbFilename;
        $thumbRelativePath = '/assets/uploads/' . $directory . '/' . $thumbFilename;
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $thumbPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $thumbPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $thumbPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb, $thumbPath, 85);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($thumb);
        
        return $thumbRelativePath;
    }
    
    /**
     * Delete uploaded file
     * 
     * @param string $relativePath Relative path from database (e.g., '/assets/uploads/news/image.jpg')
     * @return bool
     */
    public function deleteFile($relativePath) {
        if (empty($relativePath)) {
            return false;
        }
        
        // Remove leading slash and construct full path
        $relativePath = ltrim($relativePath, '/');
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/marianconnect/' . $relativePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Delete file and its thumbnail
     * 
     * @param string $relativePath Main file path
     * @param string $thumbnailPath Thumbnail path (optional)
     * @return bool
     */
    public function deleteFileWithThumbnail($relativePath, $thumbnailPath = null) {
        $mainDeleted = $this->deleteFile($relativePath);
        
        if ($thumbnailPath) {
            $this->deleteFile($thumbnailPath);
        }
        
        return $mainDeleted;
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $bytes Size in bytes
     */
    public function setMaxFileSize($bytes) {
        $this->maxFileSize = $bytes;
    }
    
    /**
     * Get errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Format bytes to human readable size
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
