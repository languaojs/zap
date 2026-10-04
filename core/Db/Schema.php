<?php

namespace Zap\Core\Db;

use PDO;
use Zap\Core\Db\Blueprint;

class Schema
{
    protected PDO $pdo;
    protected string $driver;

    public function __construct(PDO $pdo, string $driver)
    {
        $this->pdo    = $pdo;
        $this->driver = strtolower($driver);

        // Aktifkan constraint Foreign Key pada SQLite
        if ($this->driver === 'sqlite') {
            $this->pdo->exec("PRAGMA foreign_keys = ON;");
        }
    }

    public function table(string $table, callable $callback): bool
    {
        $blueprint = new Blueprint($this->driver);

        $callback($blueprint);

        $names = $blueprint->getColumnNames();
        $sqls  = $blueprint->toSql();
        $wrappedTable = $this->wrap($table);
        $columns = $blueprint->getColumns();

        foreach ($sqls as $i => $columnSql) {

            $name = $names[$i] ?? null;

            if (!$name) continue;

            $exists   = $this->hasColumn($table, $name);
            $isChange = $columns[$i]['change'] ?? false;

            if (!$exists) {
                $this->pdo->exec("ALTER TABLE {$wrappedTable} ADD COLUMN {$columnSql}");
                continue;
            }

            if ($isChange) {
                $this->modifyColumn($wrappedTable, $columnSql);
            }
        }

        return true;
    }


    public function create(string $table, callable $callback): bool
    {
        $blueprint = new Blueprint($this->driver);
        $callback($blueprint);
        $wrappedTable = $this->wrap($table);

        $sql = "CREATE TABLE IF NOT EXISTS {$wrappedTable} (\n  " .
            implode(",\n  ", $blueprint->toSql()) .
            "\n)";

        if($this->driver === 'mysql'){
            $sql .= "ENGINE=InnoDB";
        }

        $this->pdo->exec($sql);

        foreach ($blueprint->getIndexes() as $indexSql) {
            if ($this->driver === 'sqlite') {
                $sqliteIndexSql = preg_replace('/^(UNIQUE INDEX|INDEX)\s+([^\s]+)\s*\((.+)\)$/i', 'CREATE $1 $2 ON ' . $wrappedTable . ' ($3)', $indexSql);

                $this->pdo->exec($sqliteIndexSql);
            } else {
                $this->pdo->exec("ALTER TABLE {$wrappedTable} ADD {$indexSql}");
            }
        }

        return true;
    }

    public function drop(string $table): bool
    {
        $wrappedTable = $this->wrap($table);
        $this->pdo->exec("DROP TABLE IF EXISTS {$wrappedTable}");
        return true;
    }

    public function hasTable(string $table): bool
    {
        if ($this->driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = ?"
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetch();
        }

        $cleanTable = str_replace('`', '', $table);

        $stmt = $this->pdo->query(
            "SHOW TABLES LIKE " . $this->pdo->quote($cleanTable)
        );

        return (bool) $stmt->fetch();
    }

    public function addColumn(string $table, string $name, string $type): bool
    {
        $wrappedTable = $this->wrap($table);

        $this->pdo->exec(
            "ALTER TABLE {$wrappedTable} ADD COLUMN {$name} {$type}"
        );

        return true;
    }

    public function addColumnIfNotExists(string $table, string $name, string $type): bool
    {
        if ($this->hasColumn($table, $name)) {
            return true;
        }

        return $this->addColumn($table, $name, $type);
    }

    public function hasColumn(string $table, string $column): bool
    {
        $cleanTable = str_replace('`', '', $table);

        if ($this->driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA table_info({$cleanTable})");

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if ($col['name'] === $column) {
                    return true;
                }
            }

            return false;
        }

        $stmt = $this->pdo->query("DESCRIBE `{$cleanTable}`");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            if ($col['Field'] === $column) {
                return true;
            }
        }

        return false;
    }

    public function dropColumn(string $table, string $column): bool
    {
        if (!$this->hasColumn($table, $column)) {
            return true;
        }

        if ($this->driver === 'sqlite') {
            return $this->rebuildWithoutColumn($table, $column);
        }

        $wrappedTable = $this->wrap($table);
        $this->pdo->exec("ALTER TABLE {$wrappedTable} DROP COLUMN {$column}");
        return true;
    }

    public function renameColumn(string $table, string $from, string $to): bool
    {
        if (!$this->hasColumn($table, $from)) {
            return true;
        }

        if ($this->hasColumn($table, $to)) {
            return true;
        }

        if ($this->driver === 'sqlite') {
            return $this->rebuildRenameColumn($table, $from, $to);
        }

        $wrappedTable = $this->wrap($table);
        $this->pdo->exec(
            "ALTER TABLE {$wrappedTable} RENAME COLUMN {$from} TO {$to}"
        );

        return true;
    }

    protected function rebuildWithoutColumn(string $table, string $remove): bool
    {
        $cleanTable = str_replace('`', '', $table);
        $cols = $this->getColumnList($cleanTable);

        $cols = array_values(array_filter($cols, fn($c) => $c !== $remove));

        if (count($cols) === 0) {
            throw new \RuntimeException(
                "Cannot drop the last column '{$remove}' from table '{$table}'."
            );
        }

        $list = implode(',', $cols);
        $tmp  = $this->wrap("__tmp_{$cleanTable}");
        $wrappedTable = $this->wrap($cleanTable);

        $this->pdo->exec("CREATE TABLE {$tmp} AS SELECT {$list} FROM {$wrappedTable}");
        $this->pdo->exec("DROP TABLE {$wrappedTable}");
        $this->pdo->exec("ALTER TABLE {$tmp} RENAME TO {$wrappedTable}");

        return true;
    }

    protected function rebuildRenameColumn(string $table, string $from, string $to): bool
    {
        $cleanTable = str_replace('`', '', $table);
        $cols = $this->getColumnList($cleanTable);

        if (!in_array($from, $cols, true)) {
            return true;
        }

        if (in_array($to, $cols, true)) {
            return true;
        }

        $select = [];

        foreach ($cols as $c) {
            if ($c === $from) {
                $select[] = "{$from} AS {$to}";
            } else {
                $select[] = $c;
            }
        }

        $selectStr = implode(',', $select);
        $tmp       = $this->wrap("__tmp_{$cleanTable}");
        $wrappedTable = $this->wrap($cleanTable);

        $this->pdo->exec("CREATE TABLE {$tmp} AS SELECT {$selectStr} FROM {$wrappedTable}");
        $this->pdo->exec("DROP TABLE {$wrappedTable}");
        $this->pdo->exec("ALTER TABLE {$tmp} RENAME TO {$wrappedTable}");

        return true;
    }

    protected function getColumnList(string $table): array
    {
        $cleanTable = str_replace('`', '', $table);
        $stmt = $this->pdo->query("PRAGMA table_info({$cleanTable})");

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    protected function modifyColumn(string $table, string $columnSql): void
    {
        if ($this->driver === 'sqlite') {
            throw new \RuntimeException(
                "SQLite cannot modify columns directly. Rebuild required."
            );
        }

        $this->pdo->exec(
            "ALTER TABLE {$table} MODIFY {$columnSql}"
        );
    }

    protected function wrap(string $name): string
    {
        return '`' . str_replace('`', '', $name) . '`';
    }
}
