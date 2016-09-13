<?php
namespace WScore\Repository\Query;

use PDO;
use PDOStatement;

/**
 * Class SqlBuilder
 * 
 * Simple Sql builder that has restricted feature. 
 * Only for WScore/Repository use. 
 *
 * @package WScore\Repository\Query
 */
class SqlBuilder
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @var int
     */
    private $value_count = 1;

    /**
     * @var array
     */
    private $sql;

    public function __construct(PDO $pdo, array $sql)
    {
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    /**
     * @return PDOStatement
     */
    public function execSelect()
    {
        $this->cleanUp();
        $table = $this->makeTable();
        $where = $this->makeWhere();
        $order = $this->makeOrder();
        $joins = $this->makeJoin();

        $sql       = "SELECT * FROM {$table}{$joins}{$where}{$order}";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->params);

        return $statement;
    }

    /**
     * @return string
     */
    public function execCount()
    {
        $this->cleanUp();
        $table = $this->makeTable();
        $where = $this->makeWhere();
        $order = $this->makeOrder();
        $joins = $this->makeJoin();

        $sql       = "SELECT COUNT(*) AS count FROM {$table}{$joins}{$where}{$order}";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->params);

        return (int)$statement->fetch(PDO::FETCH_COLUMN, 1);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function execInsert($data)
    {
        $this->cleanUp();
        $table  = $this->makeTable();
        $into   = [];
        $values = [];
        foreach ($data as $column => $v) {
            $into[]   = $column;
            $values[] = $this->getHolderName($v);
        }
        $into   = implode(', ', $into);
        $values = implode(', ', $values);

        $sql       = "INSERT INTO {$table} ({$into}) VALUES ({$values});";
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($this->params);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function execUpdate($data)
    {
        $this->cleanUp();
        $table = $this->makeTable();
        $sets  = [];
        foreach ($data as $column => $v) {
            $sets[] = "{$column} = " . $this->getHolderName($v);
        }
        $sets  = implode(', ', $sets);
        $where = $this->makeWhere();

        $sql       = "UPDATE {$table} SET {$sets}{$where};";
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($this->params);
    }

    /**
     * @return bool
     */
    public function execDelete()
    {
        $this->cleanUp();
        $table = $this->makeTable();
        $where = $this->makeWhere();

        $sql       = "DELETE FROM {$table}{$where};";
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($this->params);
    }

    private function cleanUp()
    {
        $this->params      = [];
        $this->value_count = 1;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed|null
     */
    private function get($key, $default = null)
    {
        return array_key_exists($key, $this->sql) ? $this->sql[$key] : $default;
    }

    /**
     * @return string
     */
    private function makeTable()
    {
        if (!$table = $this->get('table')) {
            throw new \InvalidArgumentException('must have table name for sql.');
        }

        return $table;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function getHolderName($value)
    {
        $holder                = "holder_" . $this->value_count++;
        $this->params[$holder] = $value;

        return ':' . $holder;
    }

    /**
     * @return string
     */
    private function makeWhere()
    {
        $conditions = $this->get('conditions', []);
        $where      = [];
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = " . $this->getHolderName($value);
        }

        if (empty($where)) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * @return string
     */
    private function makeOrder()
    {
        $orderBy = $this->get('orderBy', []);
        $order   = [];
        foreach ($orderBy as $spec) {
            $column  = $spec[0];
            $dir     = $spec[1];
            $order[] = "{$column} {$dir}";
        }

        if (empty($order)) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $order);
    }

    /**
     * @return string
     */
    private function makeJoin()
    {
        $sql = [];
        $joins = $this->get('join', []);
        foreach($joins as $join) {
            $table = $join[0];
            $using = [];
            foreach($join[1] as $col1 => $col2) {
                $using[] = "{$col1}={$col2}";
            }
            $using = implode(', ', $using);
            $sql[] = "JOIN {$table} ON( {$using} )";
        }
        $sql = implode(' ', $sql);
        if ($sql) {
            $sql = ' ' . $sql . ' ';
        }
        
        return $sql;
    }
}