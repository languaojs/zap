<?php

/**
 * bootstrap/bindings.php
 */

use Zap\Core\Utils\ArrayEngine;
use Zap\Core\Utils\Assets;
use Zap\Core\Utils\Container;
use Zap\Core\Utils\SessionManager;
use Zap\Core\Db\Database;
use Zap\Core\Config\Config;
use Zap\Core\Http\Logger;
use Zap\Core\Http\ErrorHandler;
use Zap\Core\Utils\Email;
use Zap\Core\Utils\Envy;

$container = Container::getInstance();

$container->singleton(Config::class, function($c){
    return new Config(BASE_PATH . '/config');
});

$container->singleton(Database::class, function($c){
    $config = $c->make(Config::class);
    return Database::instance($config->get('database'));
});

$container->singleton(Envy::class, function($c){
    return new Envy(BASE_PATH);
});

$container->singleton(SessionManager::class, function($c){
    return new SessionManager;
});

$container->bind(ArrayEngine::class, function($c, $params){
    return new ArrayEngine($params['array']??[]);
});


$container->singleton(Assets::class, function($c){
    return new Assets;
});

$container->singleton(Logger::class, function($c){
    return new Logger();
});

$container->singleton(ErrorHandler::class, function($c){
    return new ErrorHandler;
});

$container->singleton(Email::class, function($c){
    return new Email();
});