<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $_description ?? '' }}">
    <meta name="robots" content="{{ $_robots ?? 'index, follow' }}">
    <meta name="base-url" content="{{ base_url() }}">
    {!! service('\Zap\Core\Utils\Security')->get_instance()->set_meta_token() !!}
    <title>{{ $_title ?? 'My App' }}</title>
    
    @if (isset($assets))
        @foreach ($assetsSetter->loadCssAssets($assets['source'], $assets['header_css']) as $css)
            {!! $css !!}
        @endforeach
        @foreach ($assetsSetter->loadJsAssets($assets['source'], $assets['header_js']) as $js)
            {!! $js !!}
        @endforeach
    @endif

    @if (isset($vite) && !empty($vite))
        @foreach ($assetsSetter->vite($vite) as $tag)
            {!! $tag !!}
        @endforeach
    @endif
    
    @stack('styles')
</head>

<body class="{{ $_bodyClass ?? '' }}">
    @if (isset($navbar) && !empty($navbar))
        @include($navbar)
    @endif

    {!! service('Zap\Core\Utils\Flasher')->flash() !!}
    
    @yield('content')
    @if (isset($assets))
        @foreach ($assetsSetter->loadJsAssets($assets['source'], $assets['footer_js']) as $js)
            {!! $js !!}
        @endforeach
    @endif
    <script>
        window.AppConfig = {
            baseUrl: "{{ base_url() }}"
        };
        if(typeof AOS !=='undefined' && typeof AOS.init === 'function'){
            AOS.init();
        }
    </script>
    @stack('scripts')
    @if (isset($footer) && !empty($footer))
        @include($footer)
    @endif
</body>
</html>
