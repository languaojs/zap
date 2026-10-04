<?php

namespace Zap\Core\Config;

class Config
{
    protected array $items = [];

    public function __construct(string $configPath)
    {
        $this->loadFromDirectory($configPath);
    }

    protected function loadFromDirectory(string $path): void
    {
        foreach (glob($path . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $array = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}