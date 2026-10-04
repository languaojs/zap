<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;

class Generator extends Command
{
    protected string $name = 'generate';

    protected string $description =
        'Generate a new key or create/update the .env file.';

    public function execute(array $args = [])
    {
        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        if (count($args) > 1) {
            $this->line("Only one option allowed at a time.", 'red');
            $this->printUsage();
            return;
        }

        match ($args[0]) {
            '--key', '-k' => $this->generateKey(),
            '--env', '-e' => $this->generateEnvFile(),
            default       => $this->invalidFlag()
        };
    }

    protected function generateKey(): void
    {
        $key = bin2hex(random_bytes(32));

        $this->line("✔ Generated Key:", 'green');
        $this->line($key, 'cyan');
        $this->line("\nCopy this into .env ZAP_KEY", 'blue');
    }

    protected function generateEnvFile(): void
    {
        $envPath = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
        $shouldWrite = true;
        $action = 'created';

        if (file_exists($envPath)) {

            $this->line(".env already exists", 'yellow');
            $this->line("This will OVERWRITE the file.", 'red');

            do {
                $answer = strtolower(trim(readline("Overwrite? (yes/no): ")));

                if (in_array($answer, ['y', 'yes'])) {
                    $action = 'updated';
                    break;
                }

                if (in_array($answer, ['n', 'no'])) {
                    $this->line("Aborted.", 'red');
                    return;
                }

            } while (true);

        } else {
            $this->line(".env not found — creating from stub", 'blue');
        }


        $stub = $this->get_stub('env.stub');

        if ($stub['error'] !== 0) {
            $this->line($stub['message'], 'red');
            return;
        }

        $result = $this->put_content($envPath, $stub['data']);

        if ($result['error'] !== 0) {
            $this->line($result['message'], 'red');
            return;
        }

        $this->line("✔ .env {$action} successfully", 'green');
    }

    protected function invalidFlag(): void
    {
        $this->line("Invalid option provided.", 'red');
        $this->printUsage();
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Generator Command',
            'php console generate [options]',
            [
                '-k, --key'  => 'Generate new application key',
                '-e, --env'  => 'Create or overwrite .env from stub',
                '-h, --help' => 'Show this help',
            ],
            [
                'php console generate --key',
                'php console generate --env',
            ]
        );
    }
}
