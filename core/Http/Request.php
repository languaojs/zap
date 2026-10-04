<?php

namespace Zap\Core\Http;

class Request
{
    protected array $get;
    protected array $post;
    protected array $server;
    protected array $files;
    protected array $headers;
    protected array $json = [];

    public function __construct(
        array $get,
        array $post,
        array $server,
        array $files
    ) {
        $this->get    = $get;
        $this->post   = $post;
        $this->server = $server;
        $this->files  = $files;

        $this->headers = $this->parseHeaders();
        $this->parseJson();
    }

    public static function capture(): static
    {
        return new static($_GET, $_POST, $_SERVER, $_FILES);
    }

    /**
     * Get the real or spoofed HTTP request method.
     */
    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Method override is only checked during POST requests
        if ($method === 'POST') {
            // 1. Check for _method in form data or JSON body
            $override = $this->post['_method']
                ?? $this->json['_method']
                ?? null;

            if ($override) {
                return strtoupper((string) $override);
            }

            // 2. Check for X-HTTP-Method-Override header (useful for AJAX)
            $headerOverride = $this->header('X-HTTP-METHOD-OVERRIDE');
            if ($headerOverride) {
                return strtoupper((string) $headerOverride);
            }
        }

        return $method;
    }

    public function uri(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = $this->server['SCRIPT_NAME'] ?? $this->server['PHP_SELF'] ?? '';

        // Standardize slashes
        $uri = str_replace('\\', '/', $uri);
        $scriptName = str_replace('\\', '/', $scriptName);

        $publicDir = dirname($scriptName);
        $projectDir = dirname($publicDir);

        // 1. Strip /public if present in URI
        if ($publicDir !== '/' && $publicDir !== '.' && str_starts_with($uri, $publicDir)) {
            $uri = substr($uri, strlen($publicDir));
        }
        // 2. Strip parent directory (e.g. /zap) if present in URI
        elseif ($projectDir !== '/' && $projectDir !== '.' && str_starts_with($uri, $projectDir)) {
            $uri = substr($uri, strlen($projectDir));
        }

        $uri = trim($uri, '/');

        return $uri === '' ? '/' : '/' . $uri;
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    public function input(string $key, $default = null)
    {
        return $this->post[$key]
            ?? $this->get[$key]
            ?? $this->json[$key]
            ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json);
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    protected function parseHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function header(string $key, $default = null)
    {
        $key = strtoupper($key);
        return $this->headers[$key] ?? $default;
    }

    protected function parseJson(): void
    {
        $contentType = $this->header('CONTENT-TYPE', '');

        if (str_contains($contentType, 'application/json')) {
            $this->json = json_decode(file_get_contents('php://input'), true) ?? [];
        }
    }

    public function ajax(): bool
    {
        return strtolower($this->header('X-REQUESTED-WITH', '')) === 'xmlhttprequest';
    }

    /**
     * Determine if the incoming request expects or prefers a JSON response.
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('ACCEPT', '');
        $requestedWith = $this->header('X-REQUESTED-WITH', '');

        return str_contains(strtolower($accept), 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest';
    }
}
