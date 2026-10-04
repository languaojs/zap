<?php 

namespace Zap\App\Middlewares;

use Zap\Core\Http\Request;
use Zap\Core\Utils\SessionManager;
use Zap\Core\Utils\Container;
use Zap\Core\Utils\Flasher;
use Zap\Core\Utils\ArrayEngine;

class AuthMiddleware {

    public function handle(Request $request, array $params, callable $next, string ...$roles){
        
        $session = Container::getInstance()->make(SessionManager::class);
        $flasher = new Flasher;
        $fallback_route = 'account.denied';
        
        if(!$session->has(config('app.session'))){
            $status = 401;
            $flasher->set('warning', 'Login', 'Anda mungkin sudah logout!');
            $this->go_to($fallback_route, $status);
        }

        $loggedData = $session->get(config('app.session'));
        $loggedRole = $loggedData['akun']['role'];

        $allowed = new ArrayEngine($roles);

        if(!$allowed->contains($loggedRole, true)){
            $status = 403;
            $flasher->set('error', 'Akses', "Anda tidak memiliki akses ke halaman {$loggedRole}!");
            $this->go_to($fallback_route, $status);
        }

        return $next($request, $params);
    }

    private function go_to(string $route, int $code){
        header('location:'. route($route, ['status'=>$code]));
        exit;
    }
}