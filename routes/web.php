<?php

/**
 * ZAP
 * /routes/web.php
 * Define routes here
 */

use Zap\Core\Routing\RouteFacade as Route;



Route::get('/', 'HomeController@index')->name('home.index')->middleware('guest');