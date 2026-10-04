<?php

namespace Zap\Core\Http;

use Throwable;

class ErrorHandler
{
    public static function render(Throwable $e, string $env = 'production'): void
    {
        try {
            $logger = service(\Zap\Core\Http\Logger::class);

            $root = self::root($e);
            $code = self::status($root);
            $ref  = bin2hex(random_bytes(5));

            $logger->error("[{$ref}] " . self::log($root, $code));

            if (PHP_SAPI === 'cli') {
                self::cli($root, $env);
                exit(1);
            }

            self::http($root, $code, $env, $ref);
            exit;

        } catch (Throwable) {
            http_response_code(500);
            echo "Critical error.";
            exit;
        }
    }

    /* ================= CORE ================= */

    protected static function root(Throwable $e): Throwable
    {
        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }
        return $e;
    }

    protected static function status(Throwable $e): int
    {
        $code = (int) $e->getCode();
        return ($code >= 100 && $code <= 599) ? $code : 500;
    }

    protected static function log(Throwable $e, int $code): string
    {
        return sprintf(
            "[%d] %s in %s:%d\n%s\n",
            $code,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

    /* ================= CATEGORY ================= */

    protected static function category(string $file): string
    {
        $file = str_replace('\\', '/', strtolower($file));

        if (str_contains($file, '/app/controllers/')) return 'Controller';
        if (str_contains($file, '/app/models/'))      return 'Model';
        if (str_contains($file, '/app/views/'))       return 'View';

        return 'Core';
    }

    /* ================= CALLER ================= */

    protected static function findCaller(array $trace): ?array
    {
        // Prefer controller
        foreach ($trace as $f) {
            if (!empty($f['file']) &&
                str_contains(str_replace('\\', '/', $f['file']), '/app/controllers/')) {
                return $f;
            }
        }

        // Fallback: any app file
        foreach ($trace as $f) {
            if (!empty($f['file']) &&
                str_contains(str_replace('\\', '/', $f['file']), '/app/')) {
                return $f;
            }
        }

        return null;
    }

    /* ================= HTTP ================= */

    protected static function http(Throwable $e, int $code, string $env, string $ref): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        if ($env !== 'development') {
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Error {$code}</title>
                <style>
                    body {
                        background: #0f172a;
                        color: #94a3b8;
                        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .error-card {
                        background: #1e293b;
                        border: 1px solid #334155;
                        padding: 40px;
                        border-radius: 12px;
                        text-align: center;
                        max-width: 400px;
                        width: 100%;
                        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
                    }
                    h1 {
                        color: #f8fafc;
                        font-size: 48px;
                        margin: 0 0 10px 0;
                    }
                    p {
                        color: #cbd5e1;
                        font-size: 16px;
                        margin: 0 0 20px 0;
                    }
                    .ref {
                        background: #0f172a;
                        color: #64748b;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-family: monospace;
                        font-size: 13px;
                        display: inline-block;
                        border: 1px solid #1e293b;
                    }
                </style>
            </head>
            <body>
                <div class='error-card'>
                    <h1>{$code}</h1>
                    <p>Something went wrong on our end. Please try again later.</p>
                    <div class='ref'>Ref: {$ref}</div>
                </div>
            </body>
            </html>
            ";
            return;
        }

        self::debug($e, $code);
    }

    /* ================= DEBUG ================= */

    protected static function debug(Throwable $e, int $code): void
    {
        $file   = $e->getFile();
        $line   = $e->getLine();
        $trace  = $e->getTrace();
        $caller = self::findCaller($trace);

        $originCategory = self::category($file);
        $callerCategory = $caller && !empty($caller['file'])
            ? self::category($caller['file'])
            : null;

        $message = htmlspecialchars($e->getMessage());
        $fileEsc = htmlspecialchars($file);

        $originPreview = self::code($file, $line);
        $callerPreview = self::callerBlock($caller);

        $traceHtml = self::trace($trace);

        echo "
        <style>
            body { background:#0f172a; color:#e5e7eb; font-family:monospace; padding:30px; }
            .card { background:#111827; padding:20px; border-radius:10px; }

            .badge { padding:4px 8px; border-radius:6px; font-size:12px; display:inline-block; margin-bottom:10px; }
            .Controller { background:#047857; }
            .Model { background:#1d4ed8; }
            .View { background:#9333ea; }
            .Core { background:#6b7280; }

            .code { background:#020617; padding:8px; border-radius:6px; }
            .line.active { background:#7f1d1d; }

            .toggle { cursor:pointer; color:#93c5fd; margin-top:10px; }
            .trace { display:none; margin-top:10px; }
        </style>

        <div class='card'>
            <h1>Error {$code}</h1>

            <div class='badge {$originCategory}'>Origin: {$originCategory}</div>
            " . ($callerCategory ? "<div class='badge {$callerCategory}'>Caller: {$callerCategory}</div>" : "") . "

            <p>{$message}</p>

            <h2>Origin (Thrown)</h2>
            <div>{$fileEsc}:{$line}</div>
            {$originPreview}

            {$callerPreview}

            <div class='toggle' onclick='t()'>▶ Full Trace</div>
            <div id='trace' class='trace'>{$traceHtml}</div>
        </div>

        <script>
            function t(){
                let el=document.getElementById('trace');
                el.style.display=el.style.display==='block'?'none':'block';
            }
        </script>
        ";
    }

    protected static function callerBlock(?array $caller): string
    {
        if (!$caller || empty($caller['file'])) return '';

        $file = $caller['file'];
        $line = $caller['line'] ?? 0;

        if (!is_readable($file)) return '';

        $fileEsc = htmlspecialchars($file);

        return "
            <h2 style='margin-top:20px'>Called at</h2>
            <div>{$fileEsc}:{$line}</div>
        " . self::code($file, $line);
    }

    /* ================= TRACE ================= */

    protected static function trace(array $trace): string
    {
        $html = '';

        foreach ($trace as $i => $f) {
            $file = htmlspecialchars($f['file'] ?? '[internal]');
            $line = $f['line'] ?? 0;

            $call = ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '');

            $html .= "<div>#{$i} {$call} — {$file}:{$line}</div>";
        }

        return $html;
    }

    /* ================= CODE ================= */

    protected static function code(string $file, int $line, int $pad = 4): string
    {
        if (!is_readable($file)) return '';

        $lines = file($file);
        $start = max($line - $pad - 1, 0);
        $end   = min($line + $pad - 1, count($lines) - 1);

        $html = "<div class='code'>";

        for ($i = $start; $i <= $end; $i++) {
            $n = $i + 1;
            $active = $n === $line ? 'active' : '';
            $content = htmlspecialchars($lines[$i]);

            $html .= "<div class='line {$active}'><b>{$n}</b> {$content}</div>";
        }

        return $html . "</div>";
    }

    /* ================= CLI ================= */

    protected static function cli(Throwable $e, string $env): void
    {
        echo "\n\033[31m{$e->getMessage()}\033[0m\n";

        if ($env === 'development') {
            echo "{$e->getFile()}:{$e->getLine()}\n";
        }

        echo "\n";
    }
}