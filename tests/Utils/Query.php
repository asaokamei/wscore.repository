<?php
namespace tests\Utils;

use PDOStatement;
use WScore\Repository\Query\QueryInterface;

class Query implements QueryInterface 
{
    public $table;
    public $fetchMode;
    public $condition = [];
    public $orderBy = [];
    public $data;
    public $keys;
    private $sql;

    public function execute($sql, $data = [])
    {
        $this->sql  = $sql;
        $this->data = $data;
    }

    /**
     * sets database table name to query.
     * should return a new object so that the object can be reused safely.
     *
     * @param string $table
     * @param null|string|array   $orderDefault
     * @return QueryInterface
     */
    public function withTable($table, $orderDefault = null)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return QueryInterface
     */
    public function newQuery()
    {
        return $this;
    }

    /**
     * returns the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * sets the fetch mode of PDOStatement.
     *
     * @param callable $callable
     * @return QueryInterface
     */
    public function setFetchMode(callable $callable)
    {
        $this->fetchMode = $callable;
        return $this;
    }

    /**
     * sets where statement from [$column_name => $value, ] to
     * WHERE $column_name = $value AND ...
     *
     * @param array $condition
     * @return QueryInterface
     */
    public function condition(array $condition)
    {
        $this->condition = array_merge($this->condition, $condition);
        return $this;
    }

    /**
     * @param array $keys
     * @return array
     */
    public function find($keys = [])
    {
        return $this->select($keys)->fetchAll();
    }

    /**
     * sets the order by clause when select.
     *
     * @param string $order
     * @param string $direction
     * @return QueryInterface
     */
    public function orderBy($order, $direction = 'ASC')
    {
        $this->orderBy[] = [$order, $direction];
        return $this;
    }

    /**
     * execute select statement with $keys as condition.
     * returns PDOStatement reflecting the self::setFetchMode().
     *
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys = [])
    {
        $this->keys = $keys;
        return new PDOStatement();
    }

    /**
     * returns the number of rows found.
     * the $keys are same as that of self::select method.
     *
     * @param array $keys
     * @return int
     */
    public function count($keys = [])
    {
        $this->keys = $keys;
        return count($keys);
    }

    /**
     * insert a data into a database table.
     * returns the new ID of auto-increment column if exists,
     * or true if no such column.
     *
     * @param array $data
     * @return string|bool
     */
    public function insert(array $data)
    {
        $this->data = $data;
        return true;
    }

    /**
     * updates the value as $data for rows selected by $keys.
     *
     * @param array $keys
     * @param array $data
     * @return bool
     */
    public function update(array $keys, array $data)
    {
        $this->keys = $keys;
        $this->data = $data;
        return true;
    }

    /**
     * deletes the rows selected by $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function delete($keys)
    {
        $this->keys = $keys;
        return true;
    }

    /**
     * JOIN clause with another $join table, with $join_on condition.
     * if $join_on is an array (keys are numeric), turns the value to
     * USING clause, for hashed array, turns to ON clause.
     *
     * @param string $join
     * @param array  $join_on
     * @return QueryInterface
     */
    public function join($join, $join_on)
    {
        throw new \BadMethodCallException('not ready');
    }

    /**
     * returns the last inserted ID.
     *
     * @param string $table
     * @param string $idName
     * @return string
     */
    public function lastId($table = '', $idName = '')
    {
        return 1;
    }
}