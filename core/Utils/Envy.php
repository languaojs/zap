<?php

namespace Zap\Core\Utils;

class Envy
{
    protected string $filePath;
    protected array $variables = [];

    public function __construct(?string $pathOrDirectory = null)
    {
        $pathOrDirectory ??= defined('BASE_PATH') ? BASE_PATH : getcwd();

        if (is_dir($pathOrDirectory)) {
            $pathOrDirectory = rtrim($pathOrDirectory, '/\\') . DIRECTORY_SEPARATOR . '.env';
        }

        $this->filePath = $pathOrDirectory;

        if (file_exists($this->filePath)) {
            $this->load_env($this->filePath);
        }
    }

    /**
     * Load and parse a .env file into environment variables.
     */
    public function load_env(?string $path = null): bool
    {
        $targetFile = $path ?? $this->filePath;

        if (!file_exists($targetFile) || !is_readable($targetFile)) {
            return false;
        }

        $content = file_get_contents($targetFile);
        $this->variables = $this->parseEnvContent($content);

        // Populate global PHP environment superglobals
        foreach ($this->variables as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
        }

        return true;
    }

    /**
     * Get an environment variable value.
     */
    public function read_env(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->variables)) {
            return $this->variables[$key];
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $envValue = getenv($key);
        if ($envValue !== false) {
            return $this->castValue($envValue);
        }

        return $default;
    }

    /**
     * Set/update an environment variable in memory and optionally persist to file.
     */
    public function set_env(string $key, mixed $value, bool $persistToFile = true): bool
    {
        $this->variables[$key] = $value;
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;

        $envStringVal = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        putenv("{$key}={$envStringVal}");

        if ($persistToFile && file_exists($this->filePath)) {
            return $this->persistValueToFile($key, $value);
        }

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->read_env($key, $default);
    }

    public function set(string $key, mixed $value, bool $persistToFile = true): bool
    {
        return $this->set_env($key, $value, $persistToFile);
    }

    public function has(string $key): bool
    {
        return $this->read_env($key) !== null;
    }

    public function all(): array
    {
        return $this->variables;
    }

    /**
     * Parses .env content into an associative array with variable resolution.
     */
    protected function parseEnvContent(string $content): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        $rawVars = [];
        $isQuotedMap = [];
        
        $inMultiline = false;
        $multilineKey = '';
        $multilineValue = '';
        $quoteChar = '';

        foreach ($lines as $line) {
            if (!$inMultiline) {
                $trimmed = trim($line);

                if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);

                if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
                    $quoteChar = $val[0];
                    $len = strlen($val);
                    $isClosedOnSameLine = ($len > 1 && $val[$len - 1] === $quoteChar && $val[$len - 2] !== '\\');

                    if ($isClosedOnSameLine) {
                        $rawVars[$key] = $this->stripQuotes(substr($val, 1, -1), $quoteChar);
                        $isQuotedMap[$key] = true;
                    } else {
                        $inMultiline = true;
                        $multilineKey = $key;
                        $multilineValue = substr($val, 1);
                    }
                } else {
                    if (str_contains($val, ' #')) {
                        $val = explode(' #', $val, 2)[0];
                        $val = trim($val);
                    }
                    $rawVars[$key] = $val;
                    $isQuotedMap[$key] = false;
                }
            } else {
                $trimmedRight = rtrim($line);
                $len = strlen($trimmedRight);
                $isEndQuote = ($len > 0 && $trimmedRight[$len - 1] === $quoteChar && ($len === 1 || $trimmedRight[$len - 2] !== '\\'));

                if ($isEndQuote) {
                    $multilineValue .= "\n" . substr($trimmedRight, 0, -1);
                    $rawVars[$multilineKey] = $this->stripQuotes($multilineValue, $quoteChar);
                    $isQuotedMap[$multilineKey] = true;

                    $inMultiline = false;
                    $multilineKey = '';
                    $multilineValue = '';
                    $quoteChar = '';
                } else {
                    $multilineValue .= "\n" . $line;
                }
            }
        }

        // Pass 1: Resolve nested variables (${VAR} / $VAR)
        $resolved = [];
        foreach ($rawVars as $key => $value) {
            $value = $this->resolveVariables((string) $value, $rawVars);

            // Pass 2: Cast unquoted values to native types (bool, null, ints/floats)
            if (!($isQuotedMap[$key] ?? false)) {
                $resolved[$key] = $this->castValue($value);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Replaces ${VAR} or $VAR placeholders with values from memory or superglobals.
     */
    protected function resolveVariables(string $value, array $context): string
    {
        return preg_replace_callback('/\${([A-Za-z0-9_]+)}|\$([A-Za-z0-9_]+)/', function ($matches) use ($context) {
            $varName = !empty($matches[1]) ? $matches[1] : $matches[2];

            if (array_key_exists($varName, $context)) {
                return (string) $context[$varName];
            }

            if (array_key_exists($varName, $this->variables)) {
                return (string) $this->variables[$varName];
            }

            return $_ENV[$varName] ?? $_SERVER[$varName] ?? getenv($varName) ?: '';
        }, $value);
    }

    /**
     * Safely updates or appends a key-value pair in the physical .env file.
     */
    protected function persistValueToFile(string $key, mixed $value): bool
    {
        $content = file_get_contents($this->filePath);
        $formattedValue = $this->formatForFile($value);
        $pairString = "{$key}={$formattedValue}";

        // Regex replacement to replace existing key while preserving outer spacing
        $pattern = "/^" . preg_quote($key, '/') . "\s*=.*/m";

        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, $pairString, $content);
        } else {
            $newContent = rtrim($content) . PHP_EOL . $pairString . PHP_EOL;
        }

        return file_put_contents($this->filePath, $newContent, LOCK_EX) !== false;
    }

    protected function castValue(string $val): mixed
    {
        $lowered = strtolower($val);
        return match ($lowered) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $val,
        };
    }

    protected function stripQuotes(string $val, string $quoteChar): string
    {
        if ($quoteChar === '"') {
            return str_replace(['\"', '\n'], ['"', "\n"], $val);
        }
        return str_replace("\'", "'", $val);
    }

    protected function formatForFile(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        $strVal = (string) $value;
        if (str_contains($strVal, "\n") || str_contains($strVal, ' ') || str_contains($strVal, '#')) {
            return '"' . str_replace(['"', "\n"], ['\"', "\n"], $strVal) . '"';
        }

        return $strVal;
    }
}