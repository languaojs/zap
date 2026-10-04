<?php

//bootstrap/app.php

use Zap\App\Middlewares\AuthMiddleware;
use Zap\Core\Routing\Router;
use Zap\Core\Routing\RouteFacade;
use Zap\App\Middlewares\GuestMiddleware;

$router = new Router();

// Register all middleware aliases here
$router->aliasMiddleware('guest', GuestMiddleware::class);
$router->aliasMiddleware('auth', AuthMiddleware::class);

RouteFacade::setRouter($router);

return $router;