<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Db\Database;
// use Zap\Core\Config\AppConfig;

class MigrationStatus extends Command
{
    protected string $name = 'migrate:status';
    protected string $description = 'Shows migration status.';

    protected string $table = 'migrations';

    public function execute(array $args = [])
    {
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
            return;
        }

        // $db_config = AppConfig::getDbConfig();

        $db_config = config('database');

        $db = Database::instance($db_config);

        $ran = array_column(
            $db->select("SELECT migration FROM {$this->table}"),
            'migration'
        );

        $files = glob(BASE_PATH . '/migrations/*.php') ?: [];
        sort($files);

        echo "Status | Migration\n";
        echo "-----------------------------\n";

        foreach ($files as $file) {
            $name = basename($file);

            $mark = in_array($name, $ran) ? $this->text('✔', 'green') : $this->text('✖', 'red');

            echo "{$mark}     | {$name}\n";
        }
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Migration Status',
            'php console migrate:status',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console migrate:status',
            ]
        );
    }
}
