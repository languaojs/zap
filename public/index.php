<?php 

/**
 * /public/index.php
 */

require_once 'constants.php';
require_once BASE_PATH . '/vendor/autoload.php';

use Zap\Core\Routing\RouteFacade;
use Zap\Core\Utils\SessionManager;
use Zap\Core\Utils\Container;

$router = require_once BASE_PATH . '/bootstrap/app.php';
$cachedPath = BASE_PATH . '/storage/cache/routes/routes.cache.php';

RouteFacade::setRouter($router);

if(!$router->loadCachedRoutes($cachedPath)) {
    require_once BASE_PATH . '/routes/web.php';
}

$session_mgr = Container::getInstance()->make(SessionManager::class);
$session_mgr->start();

$response = $router->dispatch();
$response->send();