<?php

namespace Zap\Core\Routing;

use Zap\Core\Http\Request;

class Route
{
    protected string $method;
    protected string $uri;
    /** @var callable|array|string */
    protected mixed $action;
    protected ?string $name = null;
    protected array $middleware = [];

    // Performance: Store pre-compiled regex and parameters natively for caching/reuse
    protected string $compiledPattern;
    protected array $parameterNames = [];

    public function __construct(string $method, string $uri, mixed $action)
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;

        $this->compilePattern();
    }

    protected function compilePattern(): void
    {
        $routeUri = $this->uri === '//' ? '/' : $this->uri;

        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches): string {
            $this->parameterNames[] = $matches[1];
            return '([^/]+)';
        }, $routeUri);

        $this->compiledPattern = '#^' . $pattern . '$#';
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(string|array $middleware, array $args = []): static
    {
        if (is_string($middleware) && !empty($args)) {
            $this->middleware[] = [$middleware, $args];
        } elseif (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
    public function getUri(): string
    {
        return $this->uri;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
    public function getAction(): mixed
    {
        return $this->action;
    }

    public function matches(Request $request, array &$parameters = []): bool
    {
        if ($this->method !== strtoupper($request->method())) {
            return false;
        }

        $requestUri = '/' . trim($request->uri(), '/');
        if ($requestUri === '//') {
            $requestUri = '/';
        }

        // Utilizes pre-compiled pattern for fast regex matching
        if (preg_match($this->compiledPattern, $requestUri, $matches)) {
            array_shift($matches);
            $parameters = !empty($this->parameterNames) ? array_combine($this->parameterNames, $matches) : [];
            return true;
        }

        return false;
    }

    public function generateUrl(array $parameters = []): string
    {
        $uri = $this->uri;

        foreach ($parameters as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($uri, $placeholder)) {
                $uri = str_replace($placeholder, (string)$value, $uri);
                unset($parameters[$key]);
            }
        }

        if (!empty($parameters)) {
            $separator = str_contains($uri, '?') ? '&' : '?';
            $uri .= $separator . http_build_query($parameters);
        }

        return $uri;
    }

    /**
     * Restore route instance from exported state during cache loading.
     */
    public static function __set_state(array $array): static
    {
        $route = new static(
            $array['method'],
            $array['uri'],
            $array['action']
        );

        $route->name = $array['name'] ?? null;
        $route->middleware = $array['middleware'] ?? [];
        $route->compiledPattern = $array['compiledPattern'] ?? '';
        $route->parameterNames = $array['parameterNames'] ?? [];

        return $route;
    }
}
