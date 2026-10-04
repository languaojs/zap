<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Db\Database;
use Zap\CLI\ProgressBar;
// use Zap\Core\Config\AppConfig;

class MigrationFresh extends Command
{
    protected string $name = 'migrate:fresh';

    protected string $description =
        'Drop all tables and rerun all migrations.';

    protected string $table = 'migrations';

    public function execute(array $args = [])
    {
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        if (!$this->confirmDanger()) {
            $this->line("Cancelled.", 'yellow');
            return;
        }

        $this->fresh();
    }

    protected function fresh(): void
    {
        // $config = AppConfig::getDbConfig();
        $config = config('database');

        $db = Database::instance($config);

        $this->line("Dropping all tables...", 'blue');

        $rows = $db->select("SELECT migration FROM {$this->table}");

        if (empty($rows)) {
            $this->line("No migrations found. Nothing to rollback.", 'yellow');
        } else {
            $bar = new ProgressBar(count($rows));

            foreach ($rows as $row) {
                $file = BASE_PATH . '/database/migrations/' . $row['migration'];

                if (!file_exists($file)) {
                    $this->line("Missing file: {$row['migration']}", 'red');
                    continue;
                }

                (require $file)->down($db);

                $bar->advance($row['migration']);
            }
        }

        $db->schema()->drop($this->table);

        $this->line("\nRe-running migrations...\n", 'blue');

        (new MigrationStart())->execute([]);

        $this->line("\n✔ Database refreshed successfully.", 'green');
    }

    protected function confirmDanger(): bool
    {
        $this->line(
            "⚠  This will DROP ALL TABLES and re-run migrations.",
            'red'
        );

        $answer = strtolower(trim(readline("Continue? (yes/no): ")));

        return in_array($answer, ['y', 'yes'], true);
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Fresh Migration',
            'php console migrate:fresh',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console migrate:fresh',
            ]
        );
    }
}