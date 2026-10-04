<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;

class MakeMigration extends Command
{
    protected string $name = 'make:migration';

    protected string $description =
        'Create a new migration file using smart stub detection.';

    public function execute(array $args = [])
    {
        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        $name = $args[0];

        if (str_starts_with($name, '-')) {
            $this->line("Migration name is required.", 'red');
            $this->printUsage();
            return;
        }

        $this->createMigration($name);
    }

    protected function createMigration(string $name): void
    {
        $stubName = $this->detectStub($name);

        $stubData = $this->get_stub($stubName);

        if ($stubData['error'] !== 0) {
            $this->line("{$stubData['message']} : {$stubName}", 'red');
            return;
        }

        $timestamp = date('Y_m_d_His');
        $fileName  = "{$timestamp}_{$name}.php";
        $path      = BASE_PATH . "/migrations/{$fileName}";

        if (file_exists($path)) {
            $this->line("Migration already exists: {$fileName}", 'yellow');
            return;
        }

        $table = $this->extractTableName($name);

        $content = $this->buildTemplate($stubData['data'], $table);

        touch($path);

        $result = $this->put_content($path, $content);

        if ($result['error'] !== 0) {
            $this->line($result['message'], 'red');
            return;
        }

        $this->line("✔ Migration created: {$fileName}", 'green');
        $this->line("Stub used: {$stubName}", 'cyan');
    }

    protected function detectStub(string $name): string
    {
        $map = [

            // create_users_table
            '/^create_.*_table$/' =>
                'migration_create.stub',

            // add_email_to_users_table
            '/^add_.*_to_.*_table$/' =>
                'migration_add_column.stub',

            // drop_column_email_from_users
            '/^drop_column_.*_from_.*$/' =>
                'migration_drop_column.stub',

            // drop_table_users OR drop_users_table
            '/^drop_table_.*$|^drop_.*_table$/' =>
                'migration_drop_table.stub',

            // change_users_table
            '/^change_.*_table$/' =>
                'migration_change.stub',
        ];

        foreach ($map as $pattern => $stub) {
            if (preg_match($pattern, $name)) {
                return $stub;
            }
        }

        return 'migration_update.stub';
    }


    protected function extractTableName(string $name): string
    {
        $patterns = [
            '/create_(.*)_table/',
            '/add_.*_to_(.*)_table/',
            '/drop_column_.*_from_(.*)/',
            '/drop_table_(.*)/',
            '/drop_(.*)_table/',
            '/change_(.*)_table/',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $name, $m)) {
                return $m[1];
            }
        }

        return $name;
    }

    protected function buildTemplate(string $stub, string $table): string
    {
        return str_replace('{{table}}', $table, $stub);
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Make Migration',
            'php console make:migration <name>',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console make:migration create_users_table',
                'php console make:migration add_email_to_users_table',
                'php console make:migration drop_table_users',
                'php console make:migration drop_column_email_from_users',
                'php console make:migration change_users_table',
            ]
        );
    }
}
