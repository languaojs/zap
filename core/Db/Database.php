<?php

namespace Zap\Core\Db;

use PDO;
use PDOStatement;
use Zap\Core\Db\Schema;

class Database
{
    protected PDO $pdo;
    protected ?PDOStatement $stmt = null;
    protected string $driver;
    protected string $sqlitepath;
    protected static ?self $instance = null;

    public function __construct(array $config)
    {
        $this->driver = strtolower($config['driver'] ?? 'mysql');
        $this->sqlitepath = $config['sqlite_path'] ?? '';
        $this->pdo = $this->connect($config);
    }

    public static function instance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function schema(): Schema
    {
        return new Schema($this->pdo, $this->driver);
    }

    protected function connect(array $c): PDO
    {
        switch ($this->driver) {
            case 'sqlite':
                $dsn = "sqlite:" . $this->sqlitepath;
                $user = null;
                $pass = null;
                break;

            case 'mysql':
            default:
                $dsn = "mysql:host={$c['host']};dbname={$c['database']};charset=utf8mb4";
                $user = $c['user'] ?? null;
                $pass = $c['password'] ?? null;
                break;
        }

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function rawExecute(string $sql): int|false
    {
        return $this->pdo->exec($sql);
    }

    public function query(string $sql, array $params = []): static
    {
        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute($params);

        return $this;
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchAll(): array
    {
        return $this->stmt ? $this->stmt->fetchAll() : [];
    }

    public function fetchArray(): mixed
    {
        return $this->stmt ? $this->stmt->fetch() : false;
    }

    public function numRows(): int
    {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    public function insert(string $table, array $data): string|false
    {
        $results = QueryBuilder::insert($table, $data);
        $this->query($results['sql'], $results['params']);

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $results = QueryBuilder::update($table, $data, $where);
        $this->query($results['sql'], $results['params']);

        return $this->numRows();
    }

    public function delete(string $table, array $where): int
    {
        $results = QueryBuilder::delete($table, $where);
        $this->query($results['sql'], $results['params']);

        return $this->numRows();
    }

    /**
     * Get the ID of the last inserted row or sequence value.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }
}
