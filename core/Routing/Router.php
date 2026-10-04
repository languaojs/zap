<?php

namespace Zap\Core\Routing;

use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use Zap\Core\Http\Request;
use Zap\Core\Http\Response;
// use Zap\Core\Utils\Envy;

class Router
{
    protected string $ctrlNamespace = 'Zap\\App\\Controllers\\';

    /** @var array<string, Route[]> Grouped by HTTP method for O(1) filtering */
    protected array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    /** @var array<string, callable|string> */
    protected array $middlewareMap = [];

    /** @var ContainerInterface|null PSR-11 Container Instance */
    protected ?ContainerInterface $container = null;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function aliasMiddleware(string $name, callable|string $classOrCallable): void
    {
        $this->middlewareMap[$name] = $classOrCallable;
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    protected function addRoute(string $method, string $uri, mixed $action): Route
    {
        $method = strtoupper($method);
        $route  = new Route($method, $uri, $action);
        $this->routes[$method][] = $route;
        return $route;
    }

    /**
     * Cache routes array to disk via CLI command.
     */
    public function cacheRoutes(string $path): void
    {
        $content = "<?php\nreturn " . var_export($this->routes, true) . ";\n";
        file_put_contents($path, $content, LOCK_EX);
    }

    /**
     * Load routes from cache file to bypass file parsing in production.
     */
    public function loadCachedRoutes(string $path): bool
    {
    
        $isProd = read_env('ENVIRONMENT') === 'production';
        if ($isProd) {
            if (file_exists($path)) {
                $this->routes = require $path;
                return true;
            }
            return false;
        }
        return false;
    }

    public function route(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                if ($route->getName() === $name) {
                    return $route->generateUrl($parameters);
                }
            }
        }
        throw new Exception("Route [{$name}] not defined.");
    }

    public function dispatch(?Request $request = null): Response
    {
        $request ??= Request::capture();
        $method    = strtoupper($request->method());

        // Instantly target only the HTTP method bucket
        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $route) {
            $parameters = [];

            if ($route->matches($request, $parameters)) {
                return $this->dispatchPipeline($route, $request, $parameters);
            }
        }

        $errorMessage = sprintf('ERROR: 404 Route not found for [%s] %s', $request->method(), $request->uri());

        if (function_exists('handleError')) {
            handle_error($errorMessage, 404);
        }

        return Response::make($errorMessage, 404);
    }

    protected function dispatchPipeline(Route $route, Request $request, array $parameters): Response
    {
        $middlewareList = $route->getMiddleware();

        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function (callable $next, mixed $middlewareDefinition): callable {
                return function (Request $req, array $params) use ($next, $middlewareDefinition): Response {
                    $args          = [];
                    $middlewareKey = $middlewareDefinition;

                    if (is_array($middlewareDefinition) && count($middlewareDefinition) === 2) {
                        [$middlewareKey, $args] = $middlewareDefinition;
                        $args = (array) $args;
                    } elseif (is_string($middlewareKey) && str_contains($middlewareKey, ':')) {
                        [$middlewareKey, $argString] = explode(':', $middlewareKey, 2);
                        $args = explode(',', $argString);
                    }

                    $middleware = $this->middlewareMap[$middlewareKey] ?? $middlewareKey;

                    if (is_callable($middleware)) {
                        return $middleware($req, $params, $next, ...$args);
                    }

                    if (is_string($middleware) && class_exists($middleware)) {
                        $instance = $this->resolveClass($middleware);
                        return $instance->handle($req, $params, $next, ...$args);
                    }

                    throw new Exception("Middleware [{$middlewareKey}] cannot be resolved.");
                };
            },
            function (Request $req, array $params) use ($route): Response {
                return $this->dispatchAction($route->getAction(), $req, $params);
            }
        );

        return $pipeline($request, $parameters);
    }

    protected function dispatchAction(mixed $action, Request $request, array $parameters): Response
    {
        $result = null;

        if (is_callable($action) && !is_array($action)) {
            $reflector = new ReflectionFunction($action);
            $args      = $this->resolveParameters($reflector, $request, $parameters);
            $result    = $action(...$args);
        } elseif (is_string($action) && str_contains($action, '@')) {
            [$controllerName, $method] = explode('@', $action);

            $controllerClass = str_contains($controllerName, '\\')
                ? $controllerName
                : $this->ctrlNamespace . ucfirst($controllerName);

            if (!class_exists($controllerClass)) {
                if (function_exists('handleError')) {
                    handle_error('Controller not found', 404);
                }
                throw new Exception("Controller {$controllerClass} not found");
            }

            $instance = $this->resolveClass($controllerClass);

            if (!method_exists($instance, $method)) {
                if (function_exists('handleError')) {
                    handle_error('Method not found', 404);
                }
                throw new Exception("Method {$method} not found in {$controllerClass}");
            }

            $reflector = new ReflectionMethod($instance, $method);
            $args      = $this->resolveParameters($reflector, $request, $parameters);
            $result    = $instance->{$method}(...$args);
        } elseif (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            $instance = is_string($controller) ? $this->resolveClass($controller) : $controller;

            if (!method_exists($instance, $method)) {
                throw new Exception("Method {$method} not found in controller.");
            }

            $reflector = new ReflectionMethod($instance, $method);
            $args      = $this->resolveParameters($reflector, $request, $parameters);
            $result    = $instance->{$method}(...$args);
        } else {
            throw new Exception("Invalid route action provided.");
        }

        if ($result instanceof Response) {
            return $result;
        }

        return Response::make((string) $result, 200);
    }

    /**
     * Resolve a class instance using PSR-11 Container or Reflection auto-wiring fallback.
     */
    public function resolveClass(string $class): object
    {
        if ($this->container !== null && $this->container->has($class)) {
            return $this->container->get($class);
        }

        if (!class_exists($class)) {
            throw new Exception("Class {$class} does not exist.");
        }

        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$class} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = $this->resolveParameters($constructor);
        return $reflector->newInstanceArgs($args);
    }

    /**
     * Dynamically resolve parameters for functions, methods, or constructors.
     */
    protected function resolveParameters(
        ReflectionFunctionAbstract $reflector,
        ?Request $request = null,
        array $routeParams = []
    ): array {
        $resolved = [];
        $positionalValues = array_values($routeParams);
        $positionalIndex  = 0;

        foreach ($reflector->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // 1. Resolve typed class dependencies (DI)
            if ($paramType instanceof ReflectionNamedType && !$paramType->isBuiltin()) {
                $className = $paramType->getName();

                // Exact or subclass match for Request
                if ($request !== null && (is_a($request, $className) || $className === Request::class)) {
                    $resolved[] = $request;
                    continue;
                }

                // Retrieve from PSR-11 container if registered
                if ($this->container !== null && $this->container->has($className)) {
                    $resolved[] = $this->container->get($className);
                    continue;
                }

                // Recursive reflection auto-wiring fallback for concrete classes
                if (class_exists($className)) {
                    $resolved[] = $this->resolveClass($className);
                    continue;
                }
            }

            // 2. Named route parameter match (e.g., $id from URI /user/{id})
            if (array_key_exists($paramName, $routeParams)) {
                $resolved[] = $routeParams[$paramName];
                continue;
            }

            // 3. Positional route parameter match
            if (array_key_exists($positionalIndex, $positionalValues)) {
                $resolved[] = $positionalValues[$positionalIndex];
                $positionalIndex++;
                continue;
            }

            // 4. Default parameter value fallback
            if ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
                continue;
            }

            // 5. Nullable parameter fallback
            if ($param->allowsNull()) {
                $resolved[] = null;
                continue;
            }

            $declaringClass = $reflector instanceof ReflectionMethod
                ? $reflector->getDeclaringClass()->getName() . '::'
                : '';

            throw new Exception("Cannot resolve parameter [{$paramName}] for {$declaringClass}{$reflector->getName()}()");
        }

        return $resolved;
    }
}