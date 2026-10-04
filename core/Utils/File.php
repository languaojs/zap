<?php

namespace Zap\Core\Utils;

class File
{
    protected array $upload_config = [
        'upload_path' => BASE_PATH . '/storage/',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx'],
        'max_size' => 1048, // KB
        'encrypt_name' => true
    ];

    protected array $upload_messages = [];
    protected array $upload_data = [];
    protected array $delete_file_messages = [];
    protected string $baseUrl;
    protected string $devServer;

    public function __construct()
    {
        $this->baseUrl = base_url();
        $this->devServer = config('app.dev_server');
        // $this->devServer = AppConfig::getDevServer();
    }

    protected function base_url(string $path = ''): string
    {
        $base = rtrim($this->baseUrl, '/');

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Get path to public media asset.
     */

    public function get_media(string $path): ?string
    {
        $cleanPath = ltrim($path, '/\\');
        $fullPath = BASE_PATH . '/public/media/' . $cleanPath;
        $prefix = $this->devServer === 'NATIVE' ? 'media/' : '/public/media/';
        if ($path === '' || !file_exists($fullPath)) {
            return $this->base_url($prefix . 'media-not-found.png');
        }
        return $this->base_url($prefix . $cleanPath);
    }

    /**
     * Configure upload settings.
     */
    public function init_upload(array $upload_config): void
    {
        $this->upload_config = array_merge($this->upload_config, $upload_config);
    }

    /**
     * Process file upload. Accepts array from $_FILES['field_name'] or string field key.
     * 
     * @param array|string $field
     * @param string|null $name
     * @return bool
     */
    public function do_upload(array|string $field = 'userfile', ?string $name = null): bool
    {
        // 1. Extract file from superglobal if string key is provided
        if (is_string($field)) {
            if (!isset($_FILES[$field])) {
                $this->upload_messages['error'] = "No file was selected or invalid form field '{$field}'.";
                return false;
            }
            $file = $_FILES[$field];
        } else {
            $file = $field;
        }

        // 2. Validate upload structure
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            $this->upload_messages['error'] = "Invalid upload structure provided.";
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload stopped by PHP extension.',
            ];
            $this->upload_messages['error'] = $errors[$file['error']] ?? 'Unknown upload error.';
            return false;
        }

        // 3. Verify HTTP POST Upload Source (Prevents local file inclusion attacks on tmp_name)
        if (!is_uploaded_file((string)$file['tmp_name'])) {
            $this->upload_messages['error'] = "Security error: File was not uploaded via HTTP POST.";
            return false;
        }

        // 4. Validate file size (KB to Bytes)
        if ($file['size'] > ($this->upload_config['max_size'] * 1024)) {
            $this->upload_messages['error'] = "File size exceeds the limit of " . $this->upload_config['max_size'] . " KB.";
            return false;
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

        // 5. Strict Extension Check
        if (!in_array($ext, $this->upload_config['allowed_types'], true)) {
            $this->upload_messages['error'] = "The file extension '.{$ext}' is not allowed.";
            return false;
        }

        // Block executable extensions regardless of config
        $forbiddenExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'cgi', 'pl', 'exe'];
        if (in_array($ext, $forbiddenExtensions, true)) {
            $this->upload_messages['error'] = "Uploading executable extensions is forbidden.";
            return false;
        }

        // 6. Strict MIME Type Validation
        $mimeMap = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'pdf'  => ['application/pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string)$file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimesForExt = $mimeMap[$ext] ?? [];
        if (!in_array($mime, $allowedMimesForExt, true)) {
            $this->upload_messages['error'] = "File content (MIME: {$mime}) does not match allowed extension '.{$ext}'.";
            return false;
        }

        // 7. Secure Filename Generation
        if ($this->upload_config['encrypt_name'] && $name === null) {
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        } else {
            $rawName = $name ?? pathinfo((string)$file['name'], PATHINFO_FILENAME);
            $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', basename($rawName));
            $filename = $cleanName . '.' . $ext;
        }

        // 8. Resolve Upload Target & Check Against allowed_roots
        $rawUploadDir = $this->upload_config['upload_path'];
        $fullUploadDir = str_starts_with($rawUploadDir, BASE_PATH)
            ? $rawUploadDir
            : BASE_PATH . '/' . ltrim($rawUploadDir, '/\\');

        // Ensure directory exists first so realpath() can resolve it
        if (!is_dir($fullUploadDir)) {
            if (!@mkdir($fullUploadDir, 0755, true) && !is_dir($fullUploadDir)) {
                $this->upload_messages['error'] = "Failed to create destination upload directory.";
                return false;
            }
        }

        // Validate path safety against config('app.allowed_roots')
        $safeUploadDir = static::get_safe_path($fullUploadDir);
        if ($safeUploadDir === null) {
            $this->upload_messages['error'] = "Access denied: Target upload path outside permitted directories.";
            return false;
        }

        $targetPath = rtrim($safeUploadDir, '/\\') . '/' . $filename;

        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }

        // 9. Process Upload Move
        if (move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            $this->upload_data = [
                'file_name' => $filename,
                'file_type' => $mime,
                'file_path' => $targetPath,
                'full_path' => realpath($targetPath) ?: $targetPath,
                'file_size' => $file['size']
            ];
            return true;
        }

        $this->upload_messages['error'] = "Failed to move uploaded file.";
        return false;
    }

    public function get_upload_data(): array
    {
        return $this->upload_data;
    }

    public function get_upload_errors(): array
    {
        return $this->upload_messages;
    }

    /* ================= FILE OPERATIONS ================= */

    /**
     * Resolves a path and ensures it stays within allowed base directories.
     * Prevents path traversal (e.g. "../../etc/passwd").
     */
    protected static function get_safe_path(string $path): ?string
    {
        // Define allowed root paths
        $allowedRoots = config('app.allowed_roots');

        // Get canonical path (returns false if file does not exist)
        $realPath = realpath($path);

        if ($realPath === false) {
            return null; // File does not exist
        }

        // Verify the resolved path starts with an allowed base directory
        foreach ($allowedRoots as $allowedRoot) {
            if ($allowedRoot !== false && str_starts_with($realPath, $allowedRoot)) {
                return $realPath;
            }
        }

        return null; // Path traversal attempt detected!
    }

    public static function delete_file(string $path): array
    {
        $messages = [];
        $safePath = static::get_safe_path($path);

        if ($safePath === null) {
            return [
                'error'   => 1,
                'message' => 'File not found or access denied (Invalid path).'
            ];
        }

        if (@unlink($safePath)) {
            $messages['error'] = 0;
            $messages['message'] = 'File deleted successfully.';
        } else {
            $messages['error'] = 1;
            $messages['message'] = 'Failed to delete file.';
        }

        return $messages;
    }

    public static function get(string $path): array
    {
        $response = [
            'error'   => 1,
            'message' => '',
            'data'    => ''
        ];

        $safePath = static::get_safe_path($path);

        if ($safePath === null) {
            $response['message'] = 'File not found or access denied (Invalid path).';
            return $response;
        }

        $content = @file_get_contents($safePath);

        if ($content !== false) {
            $response['error']   = 0;
            $response['message'] = 'File read successfully.';
            $response['data']    = $content;
        } else {
            $response['message'] = 'File exists but could not be read (Permissions error).';
        }

        return $response;
    }

    public static function put(string $path, string $data): array
    {
        // 1. Resolve target relative to BASE_PATH if not already absolute
        $fullPath = str_starts_with($path, BASE_PATH)
            ? $path
            : BASE_PATH . '/' . ltrim($path, '/\\');

        $dir = dirname($fullPath);

        // 2. Ensure parent directory exists
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return [
                    'error'   => 1,
                    'message' => 'Failed to create target directory.'
                ];
            }
        }

        // 3. Validate parent directory against allowed_roots
        $safeDir = static::get_safe_path($dir);
        if ($safeDir === null) {
            return [
                'error'   => 1,
                'message' => 'Access denied or target path outside permitted directories.'
            ];
        }

        $targetFile = $safeDir . '/' . basename($fullPath);

        // 4. Atomic write with exclusive file lock (LOCK_EX)
        if (@file_put_contents($targetFile, $data, LOCK_EX) !== false) {
            return [
                'error'   => 0,
                'message' => 'File saved successfully.',
                'data'    => $targetFile
            ];
        }

        return [
            'error'   => 1,
            'message' => 'Failed to write file (Permissions error).'
        ];
    }

    public static function append(string $path, string $data): array
    {
        // 1. Resolve target relative to BASE_PATH if not already absolute
        $fullPath = str_starts_with($path, BASE_PATH)
            ? $path
            : BASE_PATH . '/' . ltrim($path, '/\\');

        $dir = dirname($fullPath);

        // 2. Ensure parent directory exists
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return [
                    'error'   => 1,
                    'message' => 'Failed to create target directory.'
                ];
            }
        }

        // 3. Validate parent directory against allowed_roots
        $safeDir = static::get_safe_path($dir);
        if ($safeDir === null) {
            return [
                'error'   => 1,
                'message' => 'Access denied or target path outside permitted directories.'
            ];
        }

        $targetFile = $safeDir . '/' . basename($fullPath);

        // 4. Add newline delimiter if file exists and has content
        $formattedData = (file_exists($targetFile) && filesize($targetFile) > 0)
            ? PHP_EOL . $data
            : $data;

        // 5. Append data atomically using FILE_APPEND and LOCK_EX
        if (@file_put_contents($targetFile, $formattedData, FILE_APPEND | LOCK_EX) !== false) {
            return [
                'error'   => 0,
                'message' => 'File appended successfully.',
                'data'    => $targetFile
            ];
        }

        return [
            'error'   => 1,
            'message' => 'Failed to append to file (Permissions error).'
        ];
    }


    public static function list_files(string $path): array
    {
        $response = [
            'error'   => 1,
            'message' => '',
            'data'    => []
        ];

        // Normalize and construct full path relative to BASE_PATH
        $fullPath = BASE_PATH . '/' . ltrim($path, '/\\');

        // Resolve safe path against allowed root directories
        $safePath = static::get_safe_path($fullPath);

        if ($safePath === null) {
            $response['message'] = 'Directory not found or access denied (Invalid path).';
            return $response;
        }

        if (!is_dir($safePath)) {
            $response['message'] = 'The specified path is not a valid directory.';
            return $response;
        }

        $folderPath = rtrim($safePath, '/\\') . '/';
        $files = @scandir($folderPath);

        if ($files === false) {
            $response['message'] = 'Failed to read directory contents (Permissions error).';
            return $response;
        }

        $fileList = [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $folderPath . $file;
                if (is_file($filePath)) {
                    $fileList[] = [
                        'name' => $file,
                        'path' => $folderPath . urlencode($file),
                    ];
                }
            }
        }

        $response['error']   = 0;
        $response['message'] = 'Directory contents retrieved successfully.';
        $response['data']    = $fileList;

        return $response;
    }

    /**
     * Safely streams a file download if it resides within any authorized download directory.
     *
     * @param string $path Relative path to BASE_PATH or full path
     * @param string|null $customFilename Optional custom name for the downloaded file
     * @return void
     */
    public static function download(string $path, ?string $customFilename = null): void
    {
        // 1. Resolve full candidate path
        $fullPath = str_starts_with($path, BASE_PATH)
            ? $path
            : BASE_PATH . '/' . ltrim($path, '/\\');

        $realPath = realpath($fullPath);

        if ($realPath === false || !is_file($realPath)) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        // 2. Retrieve allowed download roots (or fall back to allowed_roots)
        $allowedDownloadRoots = config('app.downloadable_roots');

        // 3. Verify the path resides inside at least ONE allowed directory
        $isAuthorized = false;
        foreach ($allowedDownloadRoots as $root) {
            $realRoot = realpath($root);
            if ($realRoot !== false && str_starts_with($realPath, $realRoot)) {
                $isAuthorized = true;
                break;
            }
        }

        if (!$isAuthorized) {
            http_response_code(403);
            echo "Access denied (Unauthorized download directory).";
            exit;
        }

        // 4. Set headers and stream binary file
        $filename = $customFilename ?? basename($realPath);
        $mimeType = mime_content_type($realPath) ?: 'application/octet-stream';
        $fileSize = filesize($realPath);

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $fileSize);
        header('Pragma: public');
        header('Expires: 0');

        readfile($realPath);
        exit;
    }
}
