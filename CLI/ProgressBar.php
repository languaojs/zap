<?php

namespace Zap\CLI;

class ProgressBar
{
    protected int $total;
    protected int $width;
    protected int $current = 0;
    protected bool $finished = false;

    protected array $colors = [
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'blue'   => "\033[34m",
        'reset'  => "\033[0m",
    ];

    public function __construct(int $total, int $width = 30)
    {
        $this->total = max(1, $total);
        $this->width = $width;
    }

    public function advance(string $label = ''): void
    {
        if ($this->finished) {
            return;
        }

        $this->current++;

        if ($this->current > $this->total) {
            $this->current = $this->total;
        }

        $percent = $this->current / $this->total;

        $filled = (int) floor($percent * $this->width);
        $empty  = $this->width - $filled;

        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);

        $color = $percent === 1
            ? $this->colors['green']
            : $this->colors['blue'];

        $labelText = $label ? "  {$label}" : '';

        $line = sprintf(
            "%s[%s]%s %d/%d (%d%%)%s",
            $color,
            $bar,
            $this->colors['reset'],
            $this->current,
            $this->total,
            (int)($percent * 100),
            $labelText
        );

        echo "\r\033[K{$line}" . PHP_EOL;

        if ($this->current >= $this->total) {
            $this->finished = true;
            echo PHP_EOL;
        }
    }
}
