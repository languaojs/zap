<?php

namespace Zap\Core\Db;

use InvalidArgumentException;

class QueryBuilder
{
    public static function select(
        string $table,
        string|array $columns = '*',
        array $where = [],
        array $options = []
    ): array {
        self::validateName($table);

        $cols = is_array($columns)
            ? implode(',', array_map([self::class, 'validateName'], $columns))
            : $columns;

        $sql = "SELECT {$cols} FROM {$table}";
        $params = [];

        // --- 1. Tambah Penanganan JOIN ---
        if (!empty($options['join'])) {
            $sql .= " " . self::buildJoin($options['join']);
        }

        if (!empty($where)) {
            [$wSql, $wParams] = self::buildWhere($where);
            $sql .= " WHERE {$wSql}";
            $params = array_merge($params, $wParams);
        }

        if (!empty($options['order'])) {
            $sql .= " ORDER BY " . self::sanitizeOrder($options['order']);
        }

        if (!empty($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'];
        }

        if (!empty($options['offset'])) {
            $sql .= " OFFSET " . (int)$options['offset'];
        }

        return ['sql' => $sql, 'params' => $params];
    }

    // --- 2. Method Baru untuk Build Klausa JOIN ---
    protected static function buildJoin(array $joins): string
    {
        $joinSql = [];

        foreach ($joins as $join) {
            // Validasi struktur array join
            if (count($join) < 3) {
                throw new InvalidArgumentException("Format JOIN harus memiliki minimal [table, column1, column2]");
            }

            $type = strtoupper($join[3] ?? 'INNER'); // Default: INNER JOIN
            $table = self::validateName($join[0]);
            $col1 = self::validateName($join[1]);
            $col2 = self::validateName($join[2]);

            $allowedTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS'];
            if (!in_array($type, $allowedTypes, true)) {
                throw new InvalidArgumentException("Tipe JOIN tidak valid: {$type}");
            }

            $joinSql[] = "{$type} JOIN {$table} ON {$col1} = {$col2}";
        }

        return implode(' ', $joinSql);
    }

    public static function insert(string $table, array $data): array
    {
        self::validateName($table);

        if (empty($data)) {
            throw new InvalidArgumentException('Insert data cannot be empty');
        }

        $cols = array_map([self::class, 'validateName'], array_keys($data));

        $sql = "INSERT INTO {$table} (" .
            implode(',', $cols) .
            ") VALUES (:" . implode(', :', $cols) . ")";

        return ['sql' => $sql, 'params' => $data];
    }

    public static function update(string $table, array $data, array $where): array
    {
        self::validateName($table);

        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }

        if (empty($where)) {
            throw new InvalidArgumentException('WHERE clause required for update');
        }

        $params = [];
        $set = [];

        foreach ($data as $k => $v) {
            self::validateName($k);

            $p = "set_{$k}";
            $set[] = "{$k} = :{$p}";
            $params[$p] = $v;
        }

        [$wSql, $wParams] = self::buildWhere($where, 'w');
        $params = array_merge($params, $wParams);

        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$wSql}";

        return ['sql' => $sql, 'params' => $params];
    }

    public static function delete(string $table, array $where): array
    {
        self::validateName($table);

        if (empty($where)) {
            throw new InvalidArgumentException('WHERE clause required for delete');
        }

        [$wSql, $params] = self::buildWhere($where);

        $sql = "DELETE FROM {$table} WHERE {$wSql}";

        return ['sql' => $sql, 'params' => $params];
    }

    protected static function buildWhere(array $where, string $prefix = 'w', string $join = 'AND'): array
    {
        $sqlParts = [];
        $params = [];
        $i = 0;

        foreach ($where as $key => $value) {
            if ($key === '_or') {
                if (array_is_list($value)) {
                    $orParts = [];
                    foreach ($value as $group) {
                        [$orSql, $orParams] = self::buildWhere($group, $prefix . 'or', 'AND');
                        $orParts[] = "({$orSql})";
                        $params = array_merge($params, $orParams);
                    }
                    $sqlParts[] = '(' . implode(' OR ', $orParts) . ')';
                } else {
                    [$orSql, $orParams] = self::buildWhere($value, $prefix . 'or', 'OR');
                    $sqlParts[] = "({$orSql})";
                    $params = array_merge($params, $orParams);
                }
                continue;
            }

            $cleanKey = rtrim($key, '%');
            self::validateName($cleanKey);

            // --- UBAH BAGIAN INI ---
            // Ganti titik (.) dengan underscore (_) khusus untuk nama parameter PDO
            $paramKey = str_replace('.', '_', $cleanKey);
            $param = "{$prefix}_{$paramKey}_{$i}";
            $i++;

            if ($value === null) {
                $sqlParts[] = "{$cleanKey} IS NULL";
                continue;
            }

            if (!is_array($value) && str_ends_with($key, '%')) {
                $sqlParts[] = "{$cleanKey} LIKE :{$param}";
                $params[$param] = "%{$value}%";
                continue;
            }

            if (is_array($value)) {
                if (array_is_list($value)) {
                    $op  = strtoupper($value[0]);
                    $val = $value[1] ?? null;
                } else {
                    $op  = strtoupper(key($value));
                    $val = current($value);
                }

                if ($op === 'IN') {
                    if (empty($val)) {
                        $sqlParts[] = '0=1';
                        continue;
                    }

                    $in = [];
                    foreach ($val as $k => $v) {
                        $p = "{$param}_{$k}";
                        $in[] = ":{$p}";
                        $params[$p] = $v;
                    }

                    $sqlParts[] = "{$cleanKey} IN (" . implode(',', $in) . ")";
                } elseif ($op === 'NOT NULL') {
                    $sqlParts[] = "{$cleanKey} IS NOT NULL";
                } else {
                    $sqlParts[] = "{$cleanKey} {$op} :{$param}";
                    $params[$param] = $val;
                }

                continue;
            }

            $sqlParts[] = "{$cleanKey} = :{$param}";
            $params[$param] = $value;
        }

        if (empty($sqlParts)) {
            return ['1=1', []];
        }

        return [implode(" {$join} ", $sqlParts), $params];
    }

    protected static function validateName(string $name): string
    {
        if ($name === '*') {
            return '*';
        }

        // Mengizinkan huruf, angka, underscore (_), titik (.), dan spasi untuk alias AS
        if (!preg_match('/^[a-zA-Z0-9_\.]+(\s+AS\s+[a-zA-Z0-9_]+)?$/i', trim($name))) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }

        return $name;
    }

    protected static function sanitizeOrder(string $order): string
    {
        $parts = explode(',', $order);
        $sanitized = [];

        foreach ($parts as $part) {
            $tokens = array_values(array_filter(explode(' ', trim($part))));
            if (empty($tokens)) continue;

            $col = self::validateName($tokens[0]);
            $dir = isset($tokens[1]) && strtoupper($tokens[1]) === 'DESC' ? 'DESC' : 'ASC';

            $sanitized[] = "{$col} {$dir}";
        }

        return implode(', ', $sanitized);
    }
}
