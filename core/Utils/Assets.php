<?php

namespace Zap\Core\Utils;

class Assets
{
    private string $baseUrl;
    private string $localCssPath;
    private string $localJsPath;
    private string $devServer;

    public function __construct()
    {
        $this->devServer = config('app.dev_server');
        $this->baseUrl = rtrim(base_url(), '/');

        $assetBasePath = ($this->devServer === 'NATIVE')
            ? $this->baseUrl . '/assets/'
            : $this->baseUrl . '/public/assets/';

        $this->localCssPath = $assetBasePath . 'css/';
        $this->localJsPath  = $assetBasePath . 'js/';
    }

    public function vite(string|array $entrypoints): array
    {
        $entrypoints = (array) $entrypoints;
        $env = $_ENV['ENVIRONMENT'] ?? 'production';
        $isDev = ($env === 'development');
        $viteDevServer = 'http://localhost:5173';

        $tags = [];

        // Development Mode (Bypasses manifest completely)
        if ($isDev) {
            static $clientInjected = false;
            if (!$clientInjected) {
                $tags[] = '<script type="module" src="' . $viteDevServer . '/@vite/client"></script>';
                $clientInjected = true;
            }

            foreach ($entrypoints as $entry) {
                $url = $viteDevServer . '/' . ltrim($entry, '/');
                if (str_ends_with($entry, '.css')) {
                    $tags[] = '<link type="text/css" rel="stylesheet" href="' . $url . '">';
                } else {
                    $tags[] = '<script type="module" src="' . $url . '"></script>';
                }
            }

            return $tags;
        }

        // Production Mode: File on disk is ALWAYS inside /public/build/
        $manifestPath = BASE_PATH . '/public/build/.vite/manifest.json';
        if (!file_exists($manifestPath)) {
            $manifestPath = BASE_PATH . '/public/build/manifest.json';
        }

        if (!file_exists($manifestPath)) {
            return ['<!-- Vite Manifest Not Found. Run `npm run build` -->'];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Browser URL prefix varies based on dev server environment
        $urlPath = ($this->devServer === 'NATIVE') ? '/build/' : '/public/build/';
        $buildPrefix = $this->baseUrl . $urlPath;

        foreach ($entrypoints as $entry) {
            if (!isset($manifest[$entry])) {
                continue;
            }

            $item = $manifest[$entry];
            $assetUrl = $buildPrefix . $item['file'];

            if (str_ends_with($entry, '.css')) {
                $tags[] = '<link type="text/css" rel="stylesheet" href="' . $assetUrl . '">';
            } else {
                $tags[] = '<script type="module" src="' . $assetUrl . '"></script>';

                if (!empty($item['css'])) {
                    foreach ($item['css'] as $cssFile) {
                        $cssUrl = $buildPrefix . $cssFile;
                        $tags[] = '<link rel="stylesheet" href="' . $cssUrl . '">';
                    }
                }
            }
        }

        return $tags;
    }

    protected function getRegisteredAssets(string $type): array
    {
        return config($type);
    }

    protected function registered_css(): array
    {
        return $this->getRegisteredAssets('css');
    }

    protected function registered_js(): array
    {
        return $this->getRegisteredAssets('js');
    }

    private static function setDefaultAssets(): array
    {
        $header_css_assets = [];
        $header_js_assets = [];
        $footer_js_assets = [];

        $cssData = config('css');
        foreach($cssData as $key => $css){
            if(($css['default'] ?? null) === 'header'){
                $header_css_assets[] = $key;
            }
        }


        $jsData = config('js');
        foreach($jsData as $key => $js){
            if(isset($js['default'])){
                if($js['default'] === 'header'){
                    $header_js_assets[] = $key;
                }elseif($js['default'] === 'footer'){
                    $footer_js_assets[] = $key;
                }
            }
        }

        return [
            'header_css' => $header_css_assets,
            'header_js'  => $header_js_assets,
            'footer_js'  => $footer_js_assets,
        ];
    }

    public function setAssets(string $source, array $header_css = [], array $header_js = [], array $footer_js = []): array
    {
        $assets = self::setDefaultAssets();

        return [
            'source'     => $source,
            'header_css' => array_unique(array_merge($assets['header_css'], $header_css)),
            'header_js'  => array_unique(array_merge($assets['header_js'], $header_js)),
            'footer_js'  => array_unique(array_merge($assets['footer_js'], $footer_js)),
        ];
    }

    public function loadCssAssets(string $source, array $cssAssetsNickname): array
    {
        $tags = [];
        $registeredCssAssets = $this->registered_css();

        foreach ($cssAssetsNickname as $cssNickname) {
            if (!isset($registeredCssAssets[$cssNickname])) {
                continue;
            }

            $asset = $registeredCssAssets[$cssNickname];

            if ($source === 'local' && $asset['local'] !== 'none') {
                $href = $this->localCssPath . $asset['local'];
            } elseif ($asset['cdn'] !== 'none') {
                $href = $asset['cdn'];
            } else {
                continue;
            }

            $tags[] = '<link type="text/css" rel="stylesheet" href="' . htmlspecialchars($href) . '">';
        }

        return $tags;
    }

    public function loadJsAssets(string $source, array $jsAssetsNickname): array
    {
        $tags = [];
        $registeredJsAssets = $this->registered_js();

        foreach ($jsAssetsNickname as $jsNickname) {
            if (!isset($registeredJsAssets[$jsNickname])) {
                continue;
            }

            $asset = $registeredJsAssets[$jsNickname];

            if ($source === 'local' && $asset['local'] !== 'none') {
                $src = $this->localJsPath . $asset['local'];
            } elseif ($asset['cdn'] !== 'none') {
                $src = $asset['cdn'];
            } else {
                continue;
            }

            $attributes = $asset['attributes'];
            $jsType = $asset['type'];
            $tags[] = '<script src="' . htmlspecialchars($src) . '" ' . 'type="' . $jsType . '" ' .   $attributes . '></script>';
        }

        return $tags;
    }
}
