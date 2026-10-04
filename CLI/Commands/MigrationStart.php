<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Db\Database;
use Zap\Core\Db\Blueprint;
use Zap\CLI\ProgressBar;
// use Zap\Core\Config\AppConfig;

class MigrationStart extends Command
{
    protected string $name = 'migrate:start';
    protected string $description = 'Runs database migrations.';

    protected string $table = 'migrations';

    public function execute(array $args = [])
    {
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        echo $this->text("Running migrations...\n", 'blue');


        // $db_config = AppConfig::getDbConfig();
        $db_config = config('database');

        $db = Database::instance($db_config);

        $this->ensureMigrationTable($db);

        $ran   = $this->getRanMigrations($db);
        $files = $this->getMigrationFiles();

        $batch = $this->nextBatch($db);

        $executed = false;
        $pending = array_filter($files, fn($f) => !in_array(basename($f), $ran));
        if (!$pending) {
            echo $this->text("Nothing to migrate.\n", 'yellow');
            return;
        }

        $progress = new ProgressBar(count($pending));

        foreach ($files as $file) {

            $name = basename($file);

            if (in_array($name, $ran)) {
                continue;
            }

            $migration = require $file;

            $migration->up($db);

            $db->insert($this->table, [
                'migration' => $name,
                'batch'     => $batch
            ]);

            $progress->advance($name);

            $executed = true;
        }

        if (!$executed) {
            echo $this->text("Nothing to migrate.\n", 'yellow');
        } else {
            echo $this->text("Migration completed.\n", 'green');
        }
    }

    protected function ensureMigrationTable(Database $db): void
    {
        if ($db->schema()->hasTable($this->table)) {
            return;
        }

        $db->schema()->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    protected function getMigrationFiles(): array
    {
        $files = glob(BASE_PATH . '/migrations/*.php') ?: [];

        sort($files);

        return $files;
    }

    protected function getRanMigrations(Database $db): array
    {
        if (!$db->schema()->hasTable($this->table)) {
            return [];
        }

        $rows = $db->select("SELECT migration FROM {$this->table}");

        return array_column($rows, 'migration');
    }

    protected function nextBatch(Database $db): int
    {
        $rows = $db->select("SELECT MAX(batch) as b FROM {$this->table}");

        $max = 0;

        if (!empty($rows) && isset($rows[0]['b'])) {
            $max = (int) $rows[0]['b'];
        }

        return $max + 1;
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Migration Start',
            'php console migrate:start',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console migrate:start',
            ]
        );
    }
}
