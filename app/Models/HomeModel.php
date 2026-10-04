<?php

namespace Zap\App\Models;

use Zap\Core\Base\BaseModel;
use Zap\Core\Utils\Container;
use Zap\Core\Db\Database;

class HomeModel extends BaseModel
{

    public function sayHello(string $name) {
        return "Hello, $name!";
    }
    
}
