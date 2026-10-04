<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Db\Database;
use Zap\CLI\ProgressBar;
// use Zap\Core\Config\AppConfig;

class MigrationRollback extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback migrations (--last or --all).';

    protected string $table = 'migrations';

    public function execute(array $args = [])
    {
        $flag = $args[0] ?? null;

        if (!$flag || !in_array($flag, ['--last', '--all'])) {
            echo $this->text("Please provide --last or --all\n", 'yellow');
            $this->printUsage();
            return;
        }

        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        // $db_config = AppConfig::getDbConfig();
        $db_config = config('database');
    
        $db = Database::instance($db_config);
        $schema = $db->schema();

        if (!$schema->hasTable($this->table)) {
            echo $this->text("Nothing to rollback.\n", 'yellow');
            return;
        }

        if ($flag === '--last') {

            $rows = $db->select(
                "SELECT migration FROM {$this->table} ORDER BY id DESC LIMIT 1"
            );

        } else {

            $rows = $db->select(
                "SELECT migration FROM {$this->table} ORDER BY id DESC"
            );
        }

        if (!$rows) {
            echo $this->text("Nothing to rollback.\n", 'yellow');
            return;
        }

        $total = count($rows);
        $bar = new ProgressBar($total);

        foreach ($rows as $row) {

            $name = $row['migration'];
            $file = BASE_PATH . "/migrations/{$name}";

            if (!file_exists($file)) {
                echo $this->text("\nfile missing (skipped)\n", 'red');
                continue;
            }

            $migration = require $file;

            if (method_exists($migration, 'down')) {
                $migration->down($db);
            }

            $db->delete($this->table, [
                'migration' => $name
            ]);

            $bar->advance($name);
        }

        echo $this->text("\nRollback completed.\n", 'green');
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Migration Rollback',
            'php console migrate:rollback',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console migrate:rollback --last',
                'php console migrate:rollback --all',
            ]
        );
    }
}
