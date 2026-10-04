<?php
// server.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the file physically exists inside the public folder, let the server serve it as-is
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Otherwise, hand the request over to public/index.php (mimicking .htaccess rewrite)
require_once __DIR__ . '/public/index.php';