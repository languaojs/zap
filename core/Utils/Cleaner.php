<?php

namespace Zap\Core\Utils;

class Cleaner
{
    protected string $logFile;
    protected string $sessionPath;
    protected string $cachePath;

    public function __construct(
        string $logFile = BASE_PATH . '/storage/logs/zaphp.log',
        string $sessionPath = BASE_PATH . '/storage/sessions',
        string $cachePath = BASE_PATH . '/storage/cache/views'
    ) {
        $this->logFile = $logFile;
        $this->sessionPath = $sessionPath;
        $this->cachePath = $cachePath;
    }

    public function clearLogs(): array
    {
        if (!is_file($this->logFile)) {
            return ['success' => false, 'message' => 'Log file not found', 'size' => 0];
        }

        $size = filesize($this->logFile);

        $handle = fopen($this->logFile, 'w');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Unable to clear log file', 'size' => 0];
        }

        fclose($handle);

        return ['success' => true, 'message' => 'Logs cleared', 'size' => $size];
    }

    public function clearSessions(): array
    {
        return $this->clearDirectory($this->sessionPath);
    }

    public function clearCache(): array
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
        return $this->clearDirectory($this->cachePath);
    }

    public function clearViewCache(): array
    {
        $viewCachePath = $this->cachePath . DIRECTORY_SEPARATOR . 'views';

        if (!is_dir($viewCachePath)) {
            mkdir($viewCachePath, 0777, true);
        }

        return $this->clearDirectory($viewCachePath);
    }

    public function clearAll(): array
    {
        $logsResult = $this->clearLogs();
        $sessionsResult = $this->clearSessions();
        $cacheResult = $this->clearCache();

        return [
            'success' => true,
            'message' => 'Logs, sessions, and views cache cleared',
            'details' => [
                'logs'     => $logsResult,
                'sessions' => $sessionsResult,
                'cache'    => $cacheResult,
            ]
        ];
    }


    public function clearDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            return ['success' => false, 'message' => 'Directory not found', 'count' => 0];
        }

        $count = 0;

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {

                $result = $this->clearDirectory($path);
                $count += $result['count'];
            } else {

            if ($file === '.gitkeep' || $file === 'index.html') continue;

                if (@unlink($path)) {
                    $count++;
                }
            }
        }

        return ['success' => true, 'message' => 'Directory cleared', 'count' => $count];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}