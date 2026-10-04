<?php

namespace Zap\Core\Base;

use Zap\Core\Db\Database;
use Zap\Core\Db\QueryBuilder;

abstract class BaseModel
{
    protected array $config;
    protected mixed $connection = null;

    public function __construct()
    {
        $this->config = config('database');
    }

    public function db(): Database{
        if($this->connection === null) {
            $this->connection = Database::instance($this->config);
        }
        return $this->connection;
    }

    public function builder(): QueryBuilder {
        return new QueryBuilder;
    }

    public function countData(string $table, array $where = [], array $options = []): int
    {
        $query = QueryBuilder::select($table, '*', $where, $options);
        return $this->db()->query($query['sql'], $query['params'])->numRows();
    }

    public function create(string $table, array $data): bool
    {
        $query = QueryBuilder::insert($table, $data);
        $create = $this->db()->query($query['sql'], $query['params']);
        
        return $create !== false;
    }

    public function read(string $callback, string $table, array $where = [], array $options = []): mixed
    {
        $query = QueryBuilder::select($table, '*', $where, $options);
        return $this->db()->query($query['sql'], $query['params'])->$callback();
    }

    public function update(string $table, array $data, array $where): bool
    {
        $query = QueryBuilder::update($table, $data, $where);
        $update = $this->db()->query($query['sql'], $query['params']);
        
        return $update !== false;
    }

    public function delete(string $table, array $where): bool
    {
        $query = QueryBuilder::delete($table, $where);
        $delete = $this->db()->query($query['sql'], $query['params']);
        
        return $delete !== false;
    }
}