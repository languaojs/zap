<?php

namespace Zap\Core\Db;

class Blueprint
{
    protected string $driver;

    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = []; // <--- Penampung Foreign Keys

    protected int $lastIndex = -1;
    protected int $lastFkIndex = -1; // <--- Index tracker untuk FK Fluent API

    public function __construct(string $driver)
    {
        $this->driver = strtolower($driver);
    }

    protected function add(string $name, string $type)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'nullable' => false,
            'default' => null,
            'autoincrement' => false,
            'primary' => false,
        ];

        $this->lastIndex = count($this->columns) - 1;

        return $this;
    }

    public function id()
    {
        if ($this->driver === 'sqlite') {
            $this->columns[] = [
                'raw' => 'id INTEGER PRIMARY KEY AUTOINCREMENT'
            ];
        } else {
            $this->columns[] = [
                'raw' => 'id INT AUTO_INCREMENT PRIMARY KEY'
            ];
        }

        return $this;
    }

    public function string(string $name, int $len = 255)
    {
        return $this->add($name, "VARCHAR($len)");
    }

    public function char(string $name, int $len = 10)
    {
        return $this->add($name, "CHAR($len)");
    }

    public function integer(string $name)
    {
        return $this->add($name, "INT");
    }

    public function bigint(string $name)
    {
        return $this->add($name, "BIGINT");
    }

    public function text(string $name)
    {
        return $this->add($name, "TEXT");
    }

    public function date(string $name)
    {
        return $this->add($name, "DATE");
    }

    public function datetime(string $name)
    {
        return $this->add($name, "DATETIME");
    }

    public function time(string $name)
    {
        return $this->add($name, "TIME");
    }

    public function decimal(string $name, int $p = 10, int $s = 2)
    {
        return $this->add($name, "DECIMAL($p,$s)");
    }

    public function boolean(string $name)
    {
        return $this->add($name, "TINYINT(1)");
    }

    public function timestamps()
    {
        $this->add('created_at', 'TIMESTAMP');
        $this->default('CURRENT_TIMESTAMP');
        $this->add('updated_at', 'TIMESTAMP');
        $this->default('CURRENT_TIMESTAMP');
        $this->columns[$this->lastIndex]['on_update'] = 'CURRENT_TIMESTAMP';
        return $this;
    }

    public function nullable()
    {
        $this->columns[$this->lastIndex]['nullable'] = true;
        return $this;
    }

    public function default($value)
    {
        $this->columns[$this->lastIndex]['default'] = $value;
        return $this;
    }

    public function unique(string $column, ?string $name = null)
    {
        $name ??= "{$column}_unique";
        $this->indexes[] = "UNIQUE INDEX {$name} ({$column})";
        return $this;
    }

    public function index(string $column, ?string $name = null)
    {
        $name ??= "{$column}_index";
        $this->indexes[] = "INDEX {$name} ({$column})";
        return $this;
    }

    // ==========================================
    // FLUENT API FOREIGN KEY
    // ==========================================

    public function foreign(string $column)
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => null,
            'on' => null,
            'onDelete' => null,
            'onUpdate' => null,
        ];

        $this->lastFkIndex = count($this->foreignKeys) - 1;

        return $this;
    }

    public function references(string $column)
    {
        if ($this->lastFkIndex >= 0) {
            $this->foreignKeys[$this->lastFkIndex]['references'] = $column;
        }
        return $this;
    }

    public function on(string $table)
    {
        if ($this->lastFkIndex >= 0) {
            $this->foreignKeys[$this->lastFkIndex]['on'] = $table;
        }
        return $this;
    }

    public function onDelete(string $action)
    {
        if ($this->lastFkIndex >= 0) {
            $this->foreignKeys[$this->lastFkIndex]['onDelete'] = strtoupper($action);
        }
        return $this;
    }

    public function onUpdate(string $action)
    {
        if ($this->lastFkIndex >= 0) {
            $this->foreignKeys[$this->lastFkIndex]['onUpdate'] = strtoupper($action);
        }
        return $this;
    }

    // ==========================================
    // GENERATE SQL (DIPERBAIKI)
    // ==========================================

    public function toSql(): array
    {
        $sql = [];

        // 1. Definisikan Kolom
        foreach ($this->columns as $col) {

            if (isset($col['raw'])) {
                $sql[] = $col['raw'];
                continue;
            }

            $line = "{$col['name']} {$col['type']}";

            if (!$col['nullable']) {
                $line .= " NOT NULL";
            }

            if ($col['default'] !== null) {
                $isExpression = in_array(strtoupper($col['default']), ['CURRENT_TIMESTAMP', 'NOW()']);

                $value = (is_string($col['default']) && !$isExpression)
                    ? "'{$col['default']}'"
                    : $col['default'];

                $line .= " DEFAULT {$value}";
            }

            if (isset($col['on_update']) && $this->driver !== 'sqlite') {
                $line .= " ON UPDATE {$col['on_update']}";
            }

            if ($col['autoincrement']) {
                if ($this->driver === 'sqlite') {
                    $line = "{$col['name']} INTEGER PRIMARY KEY AUTOINCREMENT";
                    $sql[] = $line;
                    continue;
                }

                $line .= " AUTO_INCREMENT";
            }

            if ($col['primary']) {
                $line .= " PRIMARY KEY";
            }

            $sql[] = $line;
        }

        // 2. Tambahkan Foreign Keys
        foreach ($this->foreignKeys as $fk) {
            if ($fk['column'] && $fk['references'] && $fk['on']) {
                $fkLine = "FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['on']}({$fk['references']})";

                if ($fk['onDelete']) {
                    $fkLine .= " ON DELETE {$fk['onDelete']}";
                }
                if ($fk['onUpdate']) {
                    $fkLine .= " ON UPDATE {$fk['onUpdate']}";
                }

                $sql[] = $fkLine;
            }
        }

        return $sql;
    }

    public function getColumnNames(): array
    {
        $names = [];

        foreach ($this->columns as $col) {
            if (!isset($col['raw'])) {
                $names[] = $col['name'];
            } else {
                $names[] = null;
            }
        }

        return $names;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function change()
    {
        $this->columns[$this->lastIndex]['change'] = true;
        return $this;
    }

    public function getChanges(): array
    {
        return array_filter($this->columns, fn($c) => ($c['change'] ?? false));
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function autoincrement()
    {
        $this->columns[$this->lastIndex]['autoincrement'] = true;
        $this->columns[$this->lastIndex]['primary'] = true;

        return $this;
    }

    public function primary()
    {
        $this->columns[$this->lastIndex]['primary'] = true;
        return $this;
    }

    public function enum(string $name, array $allowed)
    {
        $formatted = implode(', ', array_map(fn($item) => "'{$item}'", $allowed));

        if ($this->driver === 'sqlite') {
            return $this->add($name, "TEXT CHECK({$name} IN ({$formatted}))");
        }

        return $this->add($name, "ENUM({$formatted})");
    }
}
