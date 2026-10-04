<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Routing\RouteFacade;

class CacheRoutes extends Command
{
    protected string $name = 'cache:routes';
    protected string $description = 'Caching routes';


    public function execute(array $args = [])
    {
        if (in_array('-h', $args) || in_array('--help', $args)) {
            $this->printUsage();
            return;
        }
        $router = RouteFacade::getRouter();
        require_once BASE_PATH . '/routes/web.php';
        $cachePath = BASE_PATH . '/storage/cache/routes/routes.cache.php';
        if (!is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0755, true);
        }
        $router->cacheRoutes($cachePath);
        $this->line('Web routes cached successfully', 'green');
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Cache Routes',
            'php console cache:routes',
            [
                '-h, --help' => 'Show this help',
            ]
        );
    }
}
