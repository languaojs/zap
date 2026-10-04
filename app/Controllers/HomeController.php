<?php

namespace Zap\App\Controllers;

use Zap\Core\Base\BaseController;
use Zap\Core\Http\Request;
use Zap\Core\Utils\File;
use Zap\Core\Utils\Security;
use Zap\Core\Utils\Validator;

class HomeController extends BaseController
{
    
    public function index(Request $request)
    {       
        $data = [
            'assets' => $this->assets->setAssets(source: 'local', header_css: [], header_js: [], footer_js: []),
            // 'vite' => ['resources/css/app.css', 'resources/js/app.js'],
            '_title' => 'Zap PHP — You are all set',
            '_description' => 'Thanks for installing Zap PHP. Your framework is ready for your next idea.',
            '_robots' => 'index, follow',
            '_bodyClass' => 'bg-light',
            'navbar' => 'navbars.home-navbar',
            'footer' => 'footers.home-footer',
        ];

        $html = $this->render('home/index', $data);
        return $this->response($html);
    }
}
