<?php

namespace Zap\Core\Routing;

use Zap\Core\Routing\Router;

class RouteFacade
{
    protected static ?Router $router = null;

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    /**
     * Get the underlying Router instance.
     */
    public static function getRouter(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }

        return self::$router;
    }

    public static function get(string $uri, mixed $action): Route
    {
        return self::getRouter()->get($uri, $action);
    }

    public static function post(string $uri, mixed $action): Route
    {
        return self::getRouter()->post($uri, $action);
    }

    public static function put(string $uri, mixed $action): Route
    {
        return self::getRouter()->put($uri, $action);
    }

    public static function delete(string $uri, mixed $action): Route
    {
        return self::getRouter()->delete($uri, $action);
    }

    /**
     * Dynamically pass static calls to the underlying Router instance.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return self::getRouter()->$method(...$arguments);
    }
}