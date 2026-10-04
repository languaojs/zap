<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use PDO;
use PDOException;
// use Zap\Core\Config\AppConfig;

class MakeDatabase extends Command
{
    protected string $name = 'make:database';

    protected string $description =
        'Create a MySQL database if it does not already exist.';


    public function execute(array $args = [])
    {
        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->printUsage();
        }

        // $config = AppConfig::getDbConfig();
        $config = config('database');

        $dbName = $this->resolveDatabaseName($args[0] ?? null, $config['database'] ?? null);

        if (!$dbName) {
            $this->line("Database name cannot be empty.", 'red');
            return;
        }

        $this->createDatabase($config, $dbName);
    }

    protected function resolveDatabaseName(?string $input, ?string $default): ?string
    {
        if ($input) {
            return $input;
        }

        if ($default) {
            $answer = strtolower(trim(
                readline("Use default database '{$default}'? (y/n): ")
            ));

            if (in_array($answer, ['y', 'yes'])) {
                return $default;
            }
        }

        return trim(readline("Enter database name: "));
    }


    protected function createDatabase(array $config, string $dbName): void
    {
        try {

            $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306);

            $pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $exists = $pdo->prepare("
                SELECT SCHEMA_NAME
                FROM INFORMATION_SCHEMA.SCHEMATA
                WHERE SCHEMA_NAME = :dbname
            ");

            $exists->execute(['dbname' => $dbName]);

            if ($exists->fetch()) {
                $this->line("Database '{$dbName}' already exists.", 'yellow');
                return;
            }

            $pdo->exec("
                CREATE DATABASE `{$dbName}`
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
            ");

            $this->line("✔ Database '{$dbName}' created successfully.", 'green');

        } catch (PDOException $e) {
            $this->line("Connection failed:", 'red');
            $this->line($e->getMessage(), 'red');
        }
    }

    protected function printUsage(): void
    {
        $this->usage(
            'Zap Make Database',
            'php console make:database [name]',
            [
                '-h, --help' => 'Show this help',
            ],
            [
                'php console make:database myapp_db',
                'php console make:database   (interactive prompt)',
            ]
        );
    }
}
