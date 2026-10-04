<?php

namespace Zap\Core\Utils;

class Security
{
    private static ?self $instance = null;
    private string $tokenKey = '_csrf_token';
    private int $tokenLength = 32;
    private string $sessionKey = '_form_security_tokens';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION[$this->sessionKey]) || !is_array($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    /**
     * Singleton instance accessor
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_session_key(): string
    {
        return $this->sessionKey;
    }

    public function get_token_key(): string
    {
        return $this->tokenKey;
    }

    public function set_token_field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenKey, ENT_QUOTES, 'UTF-8'),
            $this->generate_token()
        );
    }

    public function set_meta_token(): string
    {
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            $this->generate_token()
        );
    }

    /**
     * Legacy token validation for POST requests
     */
    public function validate_token(bool $flush = false): bool
    {
        $token = $_POST[$this->tokenKey] ?? null;
        return $this->verify_and_consume($token, $flush);
    }

    /**
     * Legacy token validation for AJAX requests
     */
    public function validate_token_ajax(string $received_token): bool
    {
        return $this->verify_and_consume($received_token, false);
    }

    /**
     * Universal request token validation (POST payload or HTTP Headers)
     */
    public function validate_request(bool $flush = false): bool
    {
        $token = $this->get_request_token();
        return $this->verify_and_consume($token, $flush);
    }

    /**
     * Extracts CSRF token from $_POST or HTTP Headers
     */
    public function get_request_token(): ?string
    {
        if (!empty($_POST[$this->tokenKey]) && is_string($_POST[$this->tokenKey])) {
            return $_POST[$this->tokenKey];
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        // Normalize header lookup (case-insensitive)
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
        if (!empty($normalizedHeaders['x-csrf-token'])) {
            return $normalizedHeaders['x-csrf-token'];
        }

        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return null;
    }

    /**
     * Generates a secure CSRF token with automatic garbage collection
     */
    public function generate_token(int $maxLifetimeSeconds = 3600): string
    {
        $this->cleanup_expired_tokens($maxLifetimeSeconds);

        $token = bin2hex(random_bytes($this->tokenLength));
        $_SESSION[$this->sessionKey][$token] = time();

        return $token;
    }

    /**
     * Clears all stored tokens for the user session
     */
    public function flush_token(): void
    {
        $_SESSION[$this->sessionKey] = [];
    }

    /**
     * Recursively sanitizes strings, arrays, or objects
     */
    public function sanitize_input(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize_input'], $data);
        }

        if (is_object($data)) {
            $cloned = clone $data;
            foreach ($cloned as $key => $value) {
                $cloned->{$key} = $this->sanitize_input($value);
            }
            return $cloned;
        }

        if (is_string($data)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }

    /**
     * Removes specified blacklisted keys from array payload
     */
    public function filter_post_data(array $postdata, array $disallowed = []): array
    {
        return array_diff_key($postdata, array_flip($disallowed));
    }

    /**
     * Internal helper to verify and optionally flush a specific token
     */
    private function verify_and_consume(?string $token, bool $flush): bool
    {
        if (empty($token) || !isset($_SESSION[$this->sessionKey][$token])) {
            return false;
        }

        if ($flush) {
            unset($_SESSION[$this->sessionKey][$token]);
        }

        return true;
    }

    /**
     * Internal garbage collector for expired tokens
     */
    private function cleanup_expired_tokens(int $maxLifetimeSeconds): void
    {
        if (empty($_SESSION[$this->sessionKey]) || !is_array($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
            return;
        }

        $now = time();
        foreach ($_SESSION[$this->sessionKey] as $token => $timestamp) {
            if (($now - $timestamp) > $maxLifetimeSeconds) {
                unset($_SESSION[$this->sessionKey][$token]);
            }
        }
    }
}