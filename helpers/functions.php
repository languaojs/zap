<?php

/**
 * /helper/functions.php
 * Write functions here
 * Use if(!function_exists)
 */

use Zap\Core\Config\Config;
use Zap\Core\Utils\Container;
use Zap\Core\Utils\Envy;


if (!function_exists('env_loader')) {
    function env_loader(?string $base_path = null)
    {
        Container::getInstance()->make(Envy::class);
        // $envy = new Envy(BASE_PATH);
    }
}

if (!function_exists('read_env')) {
    function read_env(string $env_key, mixed $default = null): mixed
    {
        // $envy = new Envy(BASE_PATH);
        $envy = Container::getInstance()->make(Envy::class);
        return $envy->read_env($env_key, $default);
    }
}

if (!function_exists('get_protocol')) {
    function get_protocol()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    }
}

if (!function_exists('get_environment')) {
    function get_environment(): string
    {
        return read_env('ENVIRONMENT', 'production');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base_url = rtrim(read_env('BASE_URL'), '/');
        $path = $path !== '' ? '/' . ltrim($path, '/') : '';
        return get_protocol() . '://' . $base_url . $path;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Zap Debugger</title></head>';
            echo '<body style="background: #11111b; color: #cdd6f4; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; padding: 20px; margin: 0;">';
                echo '<div style="background: #1e1e2e; border-left: 4px solid #f38ba8; border-radius: 6px; padding: 16px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); overflow-x: auto; ">';
                    echo '<pre style="margin: 0; font-size: 13px; line-height: 1.5; color: #cdd6f4;">';
                        foreach ($vars as $var) {
                            var_dump($var);
                        }
                    echo "</pre>";
                echo "</div>";
            echo "</body>";
        echo "</html>";
        exit(1);
    }
}

if (!function_exists('handle_error')) {
    function handle_error(string $message, int $code)
    {
        http_response_code($code);
        throw new \Zap\Core\Http\HttpException($message, $code);
    }
}

if (!function_exists('service')) {
    function service(string $name, mixed ...$params)
    {
        if (class_exists($name)) {
            return new $name(...$params);
        } else {
            throw new Exception("Class {$name} not found!");
        }
    }
}

if (!function_exists('vite')) {
    function vite(string|array $entrypoints): string
    {
        $assets = Container::getInstance()->make(\Zap\Core\Utils\Assets::class);
        return implode("\n", $assets->vite($entrypoints));
    }
}

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        $path = Zap\Core\Routing\RouteFacade::getRouter()->route($name, $parameters);
        return base_url($path);
    }
}

if (!function_exists('config')) {
    function config(string $config): mixed
    {
        $configClass = Container::getInstance()->make(Config::class);
        return $configClass->get($config);
    }
}

if (!function_exists('arrToObject')) {
    function arrToObject(array $array): object
    {
        return json_decode(json_encode($array));
    }
}
