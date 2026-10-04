<?php

namespace Zap\Core\Utils;

class ArrayEngine
{
    public array $array = [];

    public function __construct(array $array = [])
    {
        $this->array = $array;
    }

    public function set(array $array): static
    {
        $this->array = $array;
        return $this;
    }

    public function final(): array
    {
        return $this->array;
    }

    public function all(): array
    {
        return $this->array;
    }

    public function count(): int
    {
        return count($this->array);
    }

    public function isEmpty(): bool
    {
        return empty($this->array);
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->array);
    }

    public function get($key, $default = null)
    {
        return $this->array[$key] ?? $default;
    }

    public function first()
    {
        return reset($this->array);
    }

    public function last()
    {
        return end($this->array);
    }

    public function keys(): static
    {
        $this->array = array_keys($this->array);
        return $this;
    }

    public function values(): static
    {
        $this->array = array_values($this->array);
        return $this;
    }

    public function add($key, $value = null): static
    {
        if ($value === null && is_int($key)) {
            $this->array[] = $key;
        } else {
            $this->array[$key] = $value;
        }

        return $this;
    }

    public function push(...$values): static
    {
        array_push($this->array, ...$values);
        return $this;
    }

    public function prepend($value): static
    {
        array_unshift($this->array, $value);
        return $this;
    }

    public function merge(array ...$arrays): static
    {
        $this->array = array_merge($this->array, ...$arrays);
        return $this;
    }

    public function replace(array $array): static
    {
        $this->array = array_replace($this->array, $array);
        return $this;
    }

    public function removeKey($key): static
    {
        unset($this->array[$key]);
        return $this;
    }

    public function removeValue($value, bool $strict = false): static
    {
        $this->array = array_filter(
            $this->array,
            fn($item) => $strict ? $item !== $value : $item != $value
        );

        return $this;
    }

    public function clear(): static
    {
        $this->array = [];
        return $this;
    }

    public function map(callable $callback): static
    {
        $this->array = array_map($callback, $this->array);
        return $this;
    }

    public function each(callable $callback): static
    {
        foreach ($this->array as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    public function filter(callable $callback = null): static
    {
        $this->array = array_filter($this->array, $callback);
        return $this;
    }

    public function reject(callable $callback): static
    {
        $this->array = array_filter(
            $this->array,
            fn($v, $k) => !$callback($v, $k),
            ARRAY_FILTER_USE_BOTH
        );

        return $this;
    }

    public function find(callable $callback)
    {
        foreach ($this->array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public function findKey(callable $callback)
    {
        foreach ($this->array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function contains($value, bool $strict = false): bool
    {
        return in_array($value, $this->array, $strict);
    }

    public function search($value, bool $strict = false)
    {
        return array_search($value, $this->array, $strict);
    }

    public function unique(int $flags = SORT_STRING): static
    {
        $this->array = array_unique($this->array, $flags);
        return $this;
    }

    public function reverse(): static
    {
        $this->array = array_reverse($this->array);
        return $this;
    }

    public function sort(): static
    {
        sort($this->array);
        return $this;
    }

    public function rsort(): static
    {
        rsort($this->array);
        return $this;
    }

    public function ksort(): static
    {
        ksort($this->array);
        return $this;
    }

    public function shuffle(): static
    {
        shuffle($this->array);
        return $this;
    }

    public function slice(int $offset, ?int $length = null): static
    {
        $this->array = array_slice($this->array, $offset, $length);
        return $this;
    }

    public function chunk(int $size): array
    {
        return array_chunk($this->array, $size);
    }

    public function pluck(string|int $key): static
    {
        $this->array = array_column($this->array, $key);
        return $this;
    }

    public function implode(string $separator = ','): string
    {
        return implode($separator, $this->array);
    }

    public function flip(): static
    {
        $this->array = array_flip($this->array);
        return $this;
    }

    public function diff(array $array): static
    {
        $this->array = array_diff($this->array, $array);
        return $this;
    }

    public function intersect(array $array): static
    {
        $this->array = array_intersect($this->array, $array);
        return $this;
    }

    public function equal(array $array): bool
    {
        return $this->array == $array;
    }

    public function identical(array $array): bool
    {
        return $this->array === $array;
    }

    public function sanitize(): static
    {
        array_walk_recursive($this->array, function (&$value) {
            if (is_string($value)) {
                $value = trim(strip_tags($value));
            }
        });

        return $this;
    }

    public function trim(): static
    {
        array_walk_recursive($this->array, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        return $this;
    }

    public function lowercase(): static
    {
        array_walk_recursive($this->array, function (&$value) {
            if (is_string($value)) {
                $value = mb_strtolower($value);
            }
        });

        return $this;
    }

    public function uppercase(): static
    {
        array_walk_recursive($this->array, function (&$value) {
            if (is_string($value)) {
                $value = mb_strtoupper($value);
            }
        });

        return $this;
    }

    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->array, $callback, $initial);
    }

    public function random()
    {
        if (empty($this->array)) {
            return null;
        }

        return $this->array[array_rand($this->array)];
    }

    public function only(array $keys): static
    {
        $this->array = array_intersect_key(
            $this->array,
            array_flip($keys)
        );

        return $this;
    }

    public function except(array $keys): static
    {
        $this->array = array_diff_key(
            $this->array,
            array_flip($keys)
        );

        return $this;
    }

    public function where(string $key, mixed $value): static
    {
        $this->array = array_filter(
            $this->array,
            fn($item) => isset($item[$key]) && $item[$key] == $value
        );

        return $this;
    }

    public function whereStrict(string $key, mixed $value): static
    {
        $this->array = array_filter(
            $this->array,
            fn($item) => isset($item[$key]) && $item[$key] === $value
        );

        return $this;
    }

    public function whereIn(string $key, array $values): static
    {
        $this->array = array_filter(
            $this->array,
            fn($item) => isset($item[$key]) && in_array($item[$key], $values, true)
        );

        return $this;
    }

    public function whereNotIn(string $key, array $values): static
    {
        $this->array = array_filter(
            $this->array,
            fn($item) => !isset($item[$key]) || !in_array($item[$key], $values, true)
        );

        return $this;
    }

    public function groupBy(string|callable $group): static
    {
        $result = [];

        foreach ($this->array as $item) {

            $key = is_callable($group)
                ? $group($item)
                : ($item[$group] ?? null);

            $result[$key][] = $item;
        }

        $this->array = $result;

        return $this;
    }

    public function keyBy(string $key): static
    {
        $result = [];

        foreach ($this->array as $item) {
            $result[$item[$key]] = $item;
        }

        $this->array = $result;

        return $this;
    }

    public function sortBy(string $key): static
    {
        usort($this->array, function ($a, $b) use ($key) {
            return ($a[$key] ?? null) <=> ($b[$key] ?? null);
        });

        return $this;
    }

    public function sortDescBy(string $key): static
    {
        usort($this->array, function ($a, $b) use ($key) {
            return ($b[$key] ?? null) <=> ($a[$key] ?? null);
        });

        return $this;
    }

    public function every(callable $callback): bool
    {
        foreach ($this->array as $k => $v) {
            if (!$callback($v, $k)) {
                return false;
            }
        }

        return true;
    }

    public function partition(callable $callback): array
    {
        $true = [];
        $false = [];

        foreach ($this->array as $key => $value) {

            if ($callback($value, $key)) {
                $true[$key] = $value;
            } else {
                $false[$key] = $value;
            }
        }

        return [$true, $false];
    }

    public function some(callable $callback): bool
    {
        foreach ($this->array as $k => $v) {
            if ($callback($v, $k)) {
                return true;
            }
        }

        return false;
    }

    public function none(callable $callback): bool
    {
        return !$this->some($callback);
    }

    public function sum(?string $key = null): int|float
    {
        $sum = 0;

        foreach ($this->array as $item) {

            $sum += $key
                ? ($item[$key] ?? 0)
                : $item;
        }

        return $sum;
    }

    public function avg(?string $key = null): float
    {
        if (empty($this->array)) {
            return 0;
        }

        return $this->sum($key) / count($this->array);
    }

    public function min(?string $key = null)
    {
        if (empty($this->array)) {
            return null;
        }

        if ($key === null) {
            return min($this->array);
        }

        return min(array_column($this->array, $key));
    }

    public function max(?string $key = null)
    {
        if (empty($this->array)) {
            return null;
        }

        if ($key === null) {
            return max($this->array);
        }

        return max(array_column($this->array, $key));
    }

    public function flatten(): static
    {
        $result = [];

        $walker = function ($items) use (&$walker, &$result) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $walker($item);
                } else {
                    $result[] = $item;
                }
            }
        };

        $walker($this->array);

        $this->array = $result;

        return $this;
    }

    public function collapse(): static
    {
        $result = [];

        foreach ($this->array as $item) {

            if (is_array($item)) {
                foreach ($item as $value) {
                    $result[] = $value;
                }
            } else {
                $result[] = $item;
            }
        }

        $this->array = $result;

        return $this;
    }

    public function dot(string $prepend = ''): static
    {
        $result = [];

        $walker = function (array $array, string $prefix = '') use (&$walker, &$result) {

            foreach ($array as $key => $value) {

                $newKey = $prefix === ''
                    ? $key
                    : "{$prefix}.{$key}";

                if (is_array($value) && !empty($value)) {
                    $walker($value, $newKey);
                } else {
                    $result[$newKey] = $value;
                }
            }
        };

        $walker($this->array, $prepend);

        $this->array = $result;

        return $this;
    }

    public function undot(): static
    {
        $result = [];

        foreach ($this->array as $key => $value) {

            $segments = explode('.', $key);

            $temp = &$result;

            foreach ($segments as $segment) {

                if (!isset($temp[$segment]) || !is_array($temp[$segment])) {
                    $temp[$segment] = [];
                }

                $temp = &$temp[$segment];
            }

            $temp = $value;

            unset($temp);
        }

        $this->array = $result;

        return $this;
    }

    public function getDot(string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);

        $value = $this->array;

        foreach ($segments as $segment) {

            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function setDot(string $path, mixed $value): static
    {
        $segments = explode('.', $path);

        $temp = &$this->array;

        foreach ($segments as $segment) {

            if (!isset($temp[$segment]) || !is_array($temp[$segment])) {
                $temp[$segment] = [];
            }

            $temp = &$temp[$segment];
        }

        $temp = $value;

        unset($temp);

        return $this;
    }

    public function filterNull(): static
    {
        $filter = function ($array) use (&$filter) {

            foreach ($array as $key => $value) {

                if (is_array($value)) {
                    $array[$key] = $filter($value);

                    if ($array[$key] === []) {
                        unset($array[$key]);
                    }
                } elseif ($value === null) {
                    unset($array[$key]);
                }
            }

            return $array;
        };

        $this->array = $filter($this->array);

        return $this;
    }

    public function filterEmpty(): static
    {
        $filter = function ($array) use (&$filter) {

            foreach ($array as $key => $value) {

                if (is_array($value)) {

                    $array[$key] = $filter($value);

                    if ($array[$key] === []) {
                        unset($array[$key]);
                    }
                } elseif (is_string($value) && trim($value) === '') {
                    unset($array[$key]);
                }
            }

            return $array;
        };

        $this->array = $filter($this->array);

        return $this;
    }

    public function filterFalsy(): static
    {
        $filter = function ($array) use (&$filter) {

            foreach ($array as $key => $value) {

                if (is_array($value)) {

                    $array[$key] = $filter($value);

                    if ($array[$key] === []) {
                        unset($array[$key]);
                    }
                } elseif (!$value) {
                    unset($array[$key]);
                }
            }

            return $array;
        };

        $this->array = $filter($this->array);

        return $this;
    }

    public function escapeHtml(
        int $flags = ENT_QUOTES | ENT_SUBSTITUTE,
        string $encoding = 'UTF-8'
    ): static {
        array_walk_recursive($this->array, function (&$value) use ($flags, $encoding) {

            if (is_string($value)) {
                $value = htmlspecialchars(
                    $value,
                    $flags,
                    $encoding,
                    false
                );
            }
        });

        return $this;
    }


}
