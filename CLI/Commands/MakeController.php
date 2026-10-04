<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;

class MakeController extends Command
{
    protected string $name = 'make:controller';

    protected string $description = 'Create a new controller class.';

    public function execute(array $args = [])
    {
        if (empty($args) || in_array('-h', $args) || in_array('--help', $args)) {
            $this->printUsage();
            return;
        }

        $name = $args[0];

        if (str_starts_with($name, '-')) {
            $this->line("Controller name is required.", 'red');
            return;
        }

        $this->createController($name);
    }

    protected function createController(string $name): void
    {
        $path = BASE_PATH . "/app/Controllers/{$name}.php";

        if (file_exists($path)) {
            $this->line("Controller '{$name}' already exists.", 'yellow');
            return;
        }

        $stub = $this->get_stub('controller.stub');

        if ($stub['error'] !== 0) {
            $this->line($stub['message'], 'red');
            return;
        }

        $content = str_replace(
            ['{{ClassName}}', '{{ViewDir}}'],
            [$name, strtolower($name . '/index')],
            $stub['data']
        );

        touch($path);
        $this->put_content($path, $content);

        $this->line("✔ Controller created: {$path}", 'green');
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Make Controller',
            'php console make:controller <Name>',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console make:controller UserController',
            ]
        );
    }
}