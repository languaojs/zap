<?php

namespace Zap\CLI;

use Zap\Core\Utils\File;

abstract class Command
{
    protected string $name;
    protected string $description;

    abstract public function execute(array $args);

    public function get_name()
    {
        return $this->name;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function get_stub(string $stub_name)
    {
        $stub_path = BASE_PATH
            . DIRECTORY_SEPARATOR . 'CLI'
            . DIRECTORY_SEPARATOR . 'Stubs'
            . DIRECTORY_SEPARATOR . $stub_name;

        return File::get($stub_path);
    }

    public function put_content(string $path, string $content)
    {
        return File::put($path, $content);
    }

    public function line(string $text = '', string $color = 'white'): void
    {
        echo $this->text($text, $color) . PHP_EOL;
    }

    public function text(string $text, string $color = 'white'): string
    {
        $map = [
            'red'    => 31,
            'green'  => 32,
            'yellow' => 33,
            'blue'   => 34,
            'cyan'   => 36,
            'white'  => 37,
            'gray'   => 90,
        ];

        $code = $map[$color] ?? 37;

        return "\033[{$code}m{$text}\033[0m";
    }


    protected function usage(
        string $title,
        string $usage,
        array $options = [],
        array $examples = []
    ): void {

        echo PHP_EOL;

        $this->line($title, 'cyan');
        $this->line(str_repeat('─', strlen($title)), 'cyan');

        $this->line("\nUsage:", 'yellow');
        echo "  {$usage}\n\n";

        if (!empty($options)) {
            $this->line("Options:", 'yellow');

            foreach ($options as $flag => $desc) {
                $this->optionRow($flag, $desc);
            }

            echo PHP_EOL;
        }

        if (!empty($examples)) {
            $this->line("Examples:", 'yellow');

            foreach ($examples as $ex) {
                echo "  {$ex}\n";
            }

            echo PHP_EOL;
        }
    }

    protected function optionRow(string $flag, string $desc): void
    {
        printf("  %-16s %s\n", $flag, $desc);
    }
}
