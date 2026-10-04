<?php

namespace Zap\Core\Base;

use Zap\Core\Http\Response;
use Zap\Core\Utils\Assets;
use eftec\bladeone\BladeOne;
use Zap\Core\Utils\ArrayEngine;
use Zap\Core\Utils\Container;

abstract class BaseController
{
    protected BladeOne $blade;
    protected Assets $assets;

    protected static array $models = [];
    protected string $modelNamespace = 'Zap\\App\\Models\\';
    protected mixed $arrayEngine;

    public function __construct()
    {
        
        $views = [
            BASE_PATH . '/app/Views',
        ];

        $environment = config('app.environment');

        $mode = match($environment){
            'development' => BladeOne::MODE_DEBUG,
            'production' => config('app.production_mode') === 'fast'
                ? BladeOne::MODE_FAST
                : BladeOne::MODE_AUTO,
            default => BladeOne::MODE_AUTO
        };

        $cache = BASE_PATH . '/storage/cache/views';

        // $mode = ($_ENV['ENVIRONMENT'] ?? 'development') === 'development'
        //     ? BladeOne::MODE_DEBUG
        //     : BladeOne::MODE_FAST;

        $this->blade = new BladeOne($views, $cache, $mode);
        $this->assets = Container::getInstance()->make(Assets::class);
        $this->blade->share('assetsSetter', $this->assets);
        $this->arrayEngine = Container::getInstance()->make(ArrayEngine::class);
    }

    // ===== Blade Rendering Helper =====
    public function render(string $view, array $data = []): string
    {
        try {
            $view = str_replace('/', '.', $view);
            return $this->blade->run($view, $data);
        } catch (\Throwable $e) {
            handle_error("Blade Rendering Error: " . $e->getMessage(), 500);
        }
    }

    // Keep model() and response() methods as they are...
    protected function model(string $modelName)
    {
        $class = $this->modelNamespace . ucfirst($modelName);

        if (!class_exists($class)) {
            throw new \Exception("Model {$class} not found", 500);
        }

        return self::$models[$modelName] ??= Container::getInstance()->make($class);

        // return self::$models[$modelName] ??= new $class;
    }

    // ===== Response helpers =====
    protected function response(string $content, int $status = 200): Response
    {
        return Response::make($content, $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function xml(array $data, int $status = 200): Response
    {
        return Response::xml($data, $status);
    }

    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }

}
