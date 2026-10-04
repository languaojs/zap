<?php

/**
 * ZAP
 * /bootstrap/boot.php
 * Functions run when boot
 */

use Zap\Core\Utils\Container;
use Zap\Core\Http\ErrorHandler;
use Zap\Core\Http\Logger;

env_loader();

$environment = read_env('ENVIRONMENT');
$logger = Container::getInstance()->make(Logger::class);
$errorHandler = Container::getInstance()->make(ErrorHandler::class);

if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $logger->setLevel('debug');
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    $logger->setLevel('warning');
}

set_exception_handler(fn($e) => $errorHandler::render($e, $environment));

set_error_handler(function ($severity, $message, $file, $line) {

    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () use ($environment) {

    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    $errorHandler = Container::getInstance()->make(ErrorHandler::class);

    $errorHandler::render(
        new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ),
        $environment
    );
});