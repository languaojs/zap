<?php

namespace Zap\CLI\Commands;

use Zap\CLI\Command;
use Zap\Core\Utils\Cleaner;

class Clear extends Command
{
    protected string $name = 'clear';
    protected string $description = 'Clear application resources (logs, sessions, cache, views).';

    public function execute(array $args = [])
    {
        $cleaner = new Cleaner();

        if (empty($args)) {
            return $this->clearLogs($cleaner);
        }

        $command = $args[0];

        switch ($command) {

            case 'logs':
                return $this->clearLogs($cleaner);

            case 'sessions':
                return $this->clearSessions($cleaner);

            case 'cache':
                return $this->clearCache($cleaner);

            case 'views':
                return $this->clearViewCache($cleaner);

            case 'all':
                return $this->clearAll($cleaner);

            case 'dir':
                return $this->clearCustomDir($cleaner, $args[1] ?? null);

            default:
                $flags = $this->parseFlags($args);

                if (!empty($flags['all']))     return $this->clearAll($cleaner);
                if (!empty($flags['cache']))   return $this->clearCache($cleaner);
                if (!empty($flags['views']))   return $this->clearViewCache($cleaner);
                if (!empty($flags['log']))     return $this->clearLogs($cleaner);
                if (!empty($flags['session'])) return $this->clearSessions($cleaner);

                $this->printUsage();
        }
    }

    protected function clearLogs(Cleaner $cleaner): void
    {
        $res = $cleaner->clearLogs();

        if (!$res['success']) {
            echo $this->text("⚠ {$res['message']}\n", 'yellow');
            return;
        }

        $size = Cleaner::formatBytes($res['size']);

        echo $this->text("✔ Logs cleared ({$size})\n", 'green');
    }

    protected function clearSessions(Cleaner $cleaner): void
    {
        $res = $cleaner->clearSessions();

        if (!$res['success']) {
            echo $this->text("⚠ {$res['message']}\n", 'yellow');
            return;
        }

        echo $this->text("✔ Sessions cleared ({$res['count']} files)\n", 'green');
    }

    protected function clearCache(Cleaner $cleaner): void
    {
        $res = $cleaner->clearCache();

        if (!$res['success']) {
            echo $this->text("⚠ {$res['message']}\n", 'yellow');
            return;
        }

        echo $this->text("✔ Cache cleared ({$res['count']} files)\n", 'green');
    }

    protected function clearViewCache(Cleaner $cleaner): void
    {
        $res = $cleaner->clearViewCache();

        if (!$res['success']) {
            echo $this->text("⚠ {$res['message']}\n", 'yellow');
            return;
        }

        echo $this->text("✔ Compiled views cleared ({$res['count']} files)\n", 'green');
    }

    protected function clearAll(Cleaner $cleaner): void
    {
        $this->clearLogs($cleaner);
        $this->clearSessions($cleaner);
        $this->clearCache($cleaner);

        echo $this->text("✔ All resources cleared successfully!\n", 'cyan');
    }

    protected function clearCustomDir(Cleaner $cleaner, ?string $dir): void
    {
        if (!$dir) {
            echo $this->text("⚠ Please provide a directory path\n", 'yellow');
            return;
        }

        $res = $cleaner->clearDirectory($dir);

        if (!$res['success']) {
            echo $this->text("⚠ {$res['message']}\n", 'yellow');
            return;
        }

        echo $this->text("✔ Directory cleared ({$res['count']} files)\n", 'green');
    }

    protected function parseFlags(array $args): array
    {
        $flags = [];

        foreach ($args as $arg) {
            switch ($arg) {
                case '--log':
                case '-l':
                    $flags['log'] = true;
                    break;

                case '--session':
                case '-s':
                    $flags['session'] = true;
                    break;

                case '--cache':
                case '-c':
                    $flags['cache'] = true;
                    break;

                case '--views':
                case '-v':
                    $flags['views'] = true;
                    break;

                case '--all':
                case '-a':
                    $flags['all'] = true;
                    break;
            }
        }

        return $flags;
    }

    protected function printUsage(): void
    {
        echo "\n";

        echo $this->text("Zap Clear Command\n", 'cyan');
        echo $this->text("──────────────────────────────\n", 'cyan');

        echo $this->text("Usage:\n", 'yellow');
        echo "  php console clear [command|flag]\n\n";

        echo $this->text("Commands:\n", 'yellow');
        echo "  logs        Clear logs\n";
        echo "  sessions    Clear session files\n";
        echo "  cache       Clear all application cache\n";
        echo "  views       Clear compiled Blade views\n";
        echo "  all         Clear logs, sessions, and cache\n";
        echo "  dir <path>  Clear a custom directory\n\n";

        echo $this->text("Options:\n", 'yellow');
        echo "  -l, --log       Clear logs\n";
        echo "  -s, --session   Clear sessions\n";
        echo "  -c, --cache     Clear cache\n";
        echo "  -v, --views     Clear compiled views\n";
        echo "  -a, --all       Clear all resources\n\n";

        echo $this->text("Examples:\n", 'yellow');
        echo "  php console clear\n";
        echo "  php console clear views\n";
        echo "  php console clear cache\n";
        echo "  php console clear all\n";
        echo "  php console clear -v\n";
        echo "  php console clear dir storage/tmp\n\n";
    }
}