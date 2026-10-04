<?php

namespace Zap\Core\Http;

class Logger
{
    protected static string $path = '';
    protected static string $file = 'zaphp.log';
    protected static string $level = 'warning';
    protected static array $levels = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    public function __construct(?string $path = null)
    {
        if ($path) {
            self::setPath($path);
        } elseif (empty(self::$path)) {
            $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
            self::$path = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        }
    }

    protected static function write(string $level, mixed $message): void
    {
        if (!isset(self::$levels[$level])) {
            return;
        }

        if (self::$levels[$level] < self::$levels[self::$level]) {
            return;
        }

        if (!is_dir(self::$path)) {
            mkdir(self::$path, 0755, true);
        }

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $date = date('Y-m-d H:i:s');
        $line = sprintf("[%s] [%s] %s%s", $date, strtoupper($level), $message, PHP_EOL);

        file_put_contents(self::$path . self::$file, $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(mixed $msg): void
    {
        self::write('debug', $msg);
    }

    public function info(mixed $msg): void
    {
        self::write('info', $msg);
    }

    public function warning(mixed $msg): void
    {
        self::write('warning', $msg);
    }

    public function error(mixed $msg): void
    {
        self::write('error', $msg);
    }

    public function setLevel(string $level): void
    {
        if (isset(self::$levels[$level])) {
            self::$level = $level;
        }
    }

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}