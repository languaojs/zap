<?php

namespace Zap\Core\Http;

class Http
{
    /**
     * Log an error using instance-based Logger with container/fallback support.
     */
    protected static function logError(string $message): void
    {
        if (function_exists('service')) {
            service(Logger::class)->error($message);
            return;
        }

        (new Logger())->error($message);
    }

    /**
     * Execute HTTP request using cURL.
     */
    protected static function request(string $method, string $url, array $headers = [], ?string $body = null): mixed
    {
        $ch = curl_init();

        $defaultHeaders = [
            'User-Agent' => 'ZaPHP-App/1.0',
            'Accept'     => 'application/json',
        ];

        // Merge default headers with custom headers
        $mergedHeaders = array_merge($defaultHeaders, $headers);
        $formattedHeaders = [];
        foreach ($mergedHeaders as $key => $value) {
            $formattedHeaders[] = is_string($key) ? "{$key}: {$value}" : $value;
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $formattedHeaders,
            CURLOPT_TIMEOUT        => 10,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            self::logError("HTTP {$method} request failed: " . $error);
            return null;
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Send a GET request and decode JSON response automatically.
     */
    public static function get(string $url, array $query = [], array $headers = []): mixed
    {
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return self::request('GET', $url, $headers);
    }

    /**
     * Send a POST request with JSON payload.
     */
    public static function post(string $url, array $data = [], array $headers = []): mixed
    {
        $headers['Content-Type'] = 'application/json';
        $body = json_encode($data);

        return self::request('POST', $url, $headers, $body);
    }
}