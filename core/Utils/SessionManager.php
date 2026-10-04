<?php

namespace Zap\Core\Utils;

class SessionManager
{
    private static bool $sessionStarted = false;
    protected string $key;
    protected string $cipherType = 'AES-256-CBC';
    protected bool $secure;

    public function __construct()
    {
        // Require 32-byte key for AES-256
        $this->key = config('app.key');
        $protocol = get_protocol();
        $this->secure = ($protocol === 'https');
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', '86400');
            ini_set('session.cookie_lifetime', '86400');

            session_set_cookie_params([
                'lifetime' => 86400,
                'path'     => '/',
                'secure'   => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            if (!self::$sessionStarted) {
                session_start();
                self::$sessionStarted = true;
            }
        }
    }

    /**
     * Derives separate cryptographic keys for encryption and authentication.
     */
    private function getKeys(): array
    {
        $encKey  = hash_hkdf('sha256', $this->key, 32, 'encryption-key');
        $hmacKey = hash_hkdf('sha256', $this->key, 32, 'authentication-key');
        return [$encKey, $hmacKey];
    }



    /* ==========================================
       1. ENCRYPTION & DECRYPTION (AES-256 + HMAC)
       ========================================== */

    private function encryptData(mixed $data): string
    {
        [$encKey, $hmacKey] = $this->getKeys();

        $ivLength = openssl_cipher_iv_length($this->cipherType);
        $iv = random_bytes($ivLength);

        $jsonData = json_encode($data);
        $encrypted = openssl_encrypt($jsonData, $this->cipherType, $encKey, OPENSSL_RAW_DATA, $iv);

        // Authenticate with derived HMAC key
        $hmac = hash_hmac('sha256', $iv . $encrypted, $hmacKey, true);

        return base64_encode($hmac . $iv . $encrypted);
    }

    private function decryptData(string $payload): mixed
    {
        $raw = base64_decode($payload, true);
        if (!$raw) return false;

        [$encKey, $hmacKey] = $this->getKeys();

        $ivLength = openssl_cipher_iv_length($this->cipherType);
        $hmacLength = 32;

        if (strlen($raw) < ($hmacLength + $ivLength)) {
            return false;
        }

        $hmac      = substr($raw, 0, $hmacLength);
        $iv        = substr($raw, $hmacLength, $ivLength);
        $encrypted = substr($raw, $hmacLength + $ivLength);

        // Verify signature with derived HMAC key
        $calculatedHmac = hash_hmac('sha256', $iv . $encrypted, $hmacKey, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            return false;
        }

        $decrypted = openssl_decrypt($encrypted, $this->cipherType, $encKey, OPENSSL_RAW_DATA, $iv);
        return json_decode($decrypted, true);
    }

    /**
     * Regenerates the session ID to prevent Session Fixation attacks.
     * Call this during authentication events (e.g., login, privilege escalation).
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->start();
        return session_regenerate_id($deleteOldSession);
    }

    /* ==========================================
       2. SESSION MANAGEMENT
       ========================================== */

    public function set(string $name, mixed $data): void
    {
        $this->start();
        $_SESSION[$name] = $this->encryptData($data);
    }

    public function has(string $name): bool
    {
        $this->start();
        return isset($_SESSION[$name]);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $this->start();
        if ($this->has($name)) {
            $decrypted = $this->decryptData($_SESSION[$name]);
            return $decrypted !== false ? $decrypted : $default;
        }
        return $default;
    }

    public function getVal(string $sessionName, string $key, mixed $default = null): mixed
    {
        $data = $this->get($sessionName);
        if (is_array($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }
        return $default;
    }

    /**
     * Updates one or multiple keys inside a session array.
     * 
     * Usage:
     *   $session->updateVal('user', 'name', 'Alex'); // Single key
     *   $session->updateVal('user', ['name' => 'Alex', 'role' => 'admin']); // Multiple keys
     */
    public function updateVal(string $sessionName, string|array $key, mixed $value = null): void
    {
        $data = $this->get($sessionName, []);

        if (!is_array($data)) {
            $data = [];
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $data[$k] = $v;
            }
        } else {
            $data[$key] = $value;
        }

        $this->set($sessionName, $data);
    }

    /* ==========================================
       3. COOKIE MANAGEMENT
       ========================================== */

    public function setCookie(string $name, mixed $data, int $expiryDays = 30): bool
    {
        $encryptedValue = $this->encryptData($data);

        return setcookie(
            $name,
            $encryptedValue,
            [
                'expires'  => time() + ($expiryDays * 86400),
                'path'     => '/',
                'secure'   => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function hasCookie(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public function getCookie(string $name, mixed $default = null): mixed
    {
        if ($this->hasCookie($name)) {
            $decrypted = $this->decryptData($_COOKIE[$name]);
            return $decrypted !== false ? $decrypted : $default;
        }
        return $default;
    }

    /**
     * Updates one or multiple keys inside a cookie array.
     * 
     * Usage:
     *   $session->updateCookieVal('settings', 'theme', 'dark'); // Single key
     *   $session->updateCookieVal('settings', ['theme' => 'dark', 'lang' => 'es']); // Multiple keys
     */
    public function updateCookieVal(string $cookieName, string|array $key, mixed $value = null, int $expiryDays = 30): bool
    {
        $data = $this->getCookie($cookieName, []);

        if (!is_array($data)) {
            $data = [];
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $data[$k] = $v;
            }
        } else {
            $data[$key] = $value;
        }

        return $this->setCookie($cookieName, $data, $expiryDays);
    }

    /* ==========================================
       4. DESTROY & LOGOUT
       ========================================== */

    public function unset(string $name): bool
    {
        $this->start();
        try {
            if (isset($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function destroy(?string $cookieName = null): void
    {
        $this->start();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        self::$sessionStarted = false;

        if ($cookieName !== null && isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', time() - 3600, '/');
            unset($_COOKIE[$cookieName]);
        }
    }
}
