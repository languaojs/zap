<?php 

namespace Zap\App\Middlewares;

use Zap\Core\Http\Request;
use Zap\Core\Utils\SessionManager;
use Zap\Core\Utils\Container;

class GuestMiddleware {

    public function handle(Request $request, array $params, callable $next, string ...$roles){
        $session = Container::getInstance()->make(SessionManager::class);
        if($session->has(config('app.session'))) {
            $loggedAccount = $session->get(config('app.session'));
            $route = $loggedAccount['akun']['role'] . '.index';
            $this->go_to($route);
        }
        return $next($request, $params);
    }

    private function go_to(string $route){
        header('location:'. route($route));
        exit;
    }
}