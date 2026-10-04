<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;

class MakeModel extends Command
{
    protected string $name = 'make:model';

    protected string $description =
        'Create a new model class.';

    public function execute(array $args = [])
    {
        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        $name = $args[0];

        if (str_starts_with($name, '-')) {
            $this->line("Model name is required.", 'red');
            $this->printUsage();
            return;
        }

        $this->createModel($name);
    }

    protected function createModel(string $name): void
    {
        $path = BASE_PATH . "/app/Models/{$name}.php";

        if (file_exists($path)) {
            $this->line("Model already exists: {$path}", 'yellow');
            return;
        }

        $stub = $this->get_stub('model.stub');

        if ($stub['error'] !== 0) {
            $this->line("{$stub['message']} : model.stub", 'red');
            return;
        }

        $content = $this->buildTemplate($stub['data'], $name);

        touch($path);

        $result = $this->put_content($path, $content);

        if ($result['error'] !== 0) {
            $this->line($result['message'], 'red');
            return;
        }

        $this->line("✔ Model created: {$path}", 'green');
    }


    protected function buildTemplate(string $stub, string $name): string
    {
        $table = strtolower($name) . 's';

        return str_replace(
            ['{{ClassName}}', '{{table}}'],
            [$name, $table],
            $stub
        );
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Make Model',
            'php console make:model <ModelName>',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console make:model User',
                'php console make:model Post',
            ]
        );
    }
}
