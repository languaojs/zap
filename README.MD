# Zap PHP

Zap PHP is a lightweight PHP framework for building web applications. It includes routing, BladeOne views, database utilities, request validation, session and security helpers, and a small CLI for common project tasks.

**Documentation:** [zap.zainurrahman.my.id](https://zap.zainurrahman.my.id)

## Requirements

- PHP 8.1 or later
- Composer
- A PDO driver for the database you use (MySQL or SQLite)
- Node.js and npm are optional, for frontend assets built with Vite

## Getting started

Clone or download the project, then install its PHP dependencies:

```sh
composer install
```

Copy the example environment file:

```sh
cp .env.example .env
```

On Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
```

Edit `.env` for your local setup. In particular, configure `BASE_URL`, `DEV_SERVER`, database settings, and email settings if needed. The example is set up for `localhost/zap` with WAMP/XAMPP; for the built-in PHP server, use `BASE_URL=localhost:8000` and `DEV_SERVER=NATIVE`.

Generate an application key and set the printed value as `ZAP_KEY` in `.env`:

```sh
php console generate --key
```

Keep `.env` and the generated key private. Do not reuse example credentials or keys in a public or production deployment.

Start the built-in PHP development server from the project root:

```sh
php -S localhost:8000 -t public server.php
```

Then open [http://localhost:8000](http://localhost:8000).

## Routes, controllers, and views

Register routes in `routes/web.php` using the `Route` facade:

```php
use Zap\Core\Routing\RouteFacade as Route;

Route::get('/hello', 'HelloController@index')->name('hello');
```

Controllers live in `app/Controllers/` and use the `Zap\App\Controllers` namespace. Views live in `app/Views/` and are rendered with BladeOne:

```php
$html = $this->render('hello/index', ['name' => 'Zap']);
return $this->response($html);
```

The project includes a small CLI. Run `php console list` to see available commands, or use `--help` on a command for its options. For example:

```sh
php console make:controller HelloController
```

## Frontend assets

Vite is available for projects that need a frontend build:

```sh
npm install
npm run dev
```

Build production assets with `npm run build`. Vite entry points can be passed to the `vite()` helper or configured in a view through the layout's `vite` data.

### Litewire

The project also bundles Litewire in `public/assets/js/` as an optional, attribute-driven JavaScript layer for requests and reactive UI. It supports request attributes such as `lw-get`, `lw-post`, `lw-put`, and `lw-delete`, plus state and binding directives such as `lw:state`, `lw:text`, and `lw:model`.

Enable it for a page in a controller:

```php
$data = [
    'assets' => $this->assets->setAssets(
        source: 'local',
        footer_js: ['litewire', 'litewire-auto-csrf-plugin']
    ),
];
```

The optional `litewire-auto-csrf-plugin` adds the CSRF token from the `csrf-token` meta tag to standard non-GET forms. It skips forms handled by Litewire directives. This is client-side convenience only; validate CSRF tokens on the server for state-changing requests.

## Global helpers

The functions in `helpers/functions.php` are loaded by Composer. Common helpers include:

| Helper | Purpose |
| --- | --- |
| `read_env($key, $default)` | Read an environment value. |
| `base_url($path)` | Build an absolute URL from the configured base URL. |
| `route($name, $parameters)` | Generate a URL for a named route. |
| `config($key)` | Read application configuration. |
| `service($class, ...$args)` | Instantiate a class by name. |
| `vite($entrypoints)` | Generate Vite asset tags. |
| `dd(...$values)` | Dump values and stop execution. |
| `handle_error($message, $code)` | Raise an HTTP exception with a response status. |
| `get_protocol()` / `get_environment()` | Read the current protocol and application environment. |
| `arrToObject($array)` | Convert an array to an object. |

## Utility classes

Utility classes are in `core/Utils/` under the `Zap\Core\Utils` namespace:

| Class | Purpose |
| --- | --- |
| `ArrayEngine` | Fluent operations on arrays. |
| `Assets` | Register and render local, CDN, and Vite assets. |
| `Cleaner` | Clear application logs, sessions, and caches. |
| `Container` | Resolve and register application services. |
| `DateUtil` | Date and time conversion, formatting, and arithmetic. |
| `Email` | Compose and send email with attachments. |
| `Envy` | Load and access `.env` values. |
| `File` | File/media access and upload handling. |
| `Flasher` | Queue and render session-based flash messages. |
| `Hasher` | Password hashing and verification, UUIDs, and tokens. |
| `Security` | CSRF token and input-filtering helpers. |
| `SessionManager` | Start and manage protected session data. |
| `Validator` | Validate input against field rules. |

For complete APIs, configuration details, and examples, see the [Zap PHP documentation](https://zap.zainurrahman.my.id).
