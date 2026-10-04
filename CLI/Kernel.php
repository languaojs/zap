<?php

namespace Zap\CLI;

use Zap\CLI\Command;

class Kernel
{

    protected array $commands = [];

    public function register_command(Command $command)
    {
        $this->commands[$command->get_name()] = $command;
    }

    public function handle(array $argv)
    {
        $command_name = $argv[1] ?? null;

        if (!$command_name || $command_name === 'list') {
            $this->list_commands();
            return;
        }

        if (!isset($this->commands[$command_name])) {
            echo $this->text("Command not found: {$command_name}\n", 'red');
            return;
        }

        $this->commands[$command_name]->execute(array_slice($argv, 2));
    }

    public function list_commands()
    {
        echo $this->text("Available Commands:\n\n", 'blue');
        foreach ($this->commands as $command) {
            echo sprintf("%-30s %s \n", $this->text($command->get_name(), 'cyan'), $command->get_description());
        }
    }

    protected function text(string $text, string $color)
    {
        $map = [
            'red'    => 31,
            'green'  => 32,
            'yellow' => 33,
            'blue'   => 34,
            'cyan'   => 36,
            'white'  => 37,
        ];

        $code = $map[$color] ?? 37;

        return "\033[{$code}m{$text}\033[0m";
    }
}
