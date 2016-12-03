<?php
namespace WScore\Repository\Query;

use PDOStatement;
use WScore\Repository\Entity\EntityInterface;

interface QueryInterface
{
    /**
     * @param string $sql
     * @param array  $data
     * @return PDOStatement
     */
    public function execute($sql, $data = []);

    /**
     * sets database table name to query.
     * should return a new object so that the object can be reused safely.
     *
     * @param string $table
     * @param null|string[]   $orderDefault
     * @return QueryInterface
     */
    public function withTable($table, $orderDefault = null);

    /**
     * returns the table name.
     *
     * @return string
     */
    public function getTable();

    /**
     * sets the fetch mode of PDOStatement.
     *
     * @param int   $mode
     * @param string $fetch_args
     * @param array $ctor_args
     * @return QueryInterface
     */
    public function setFetchMode($mode, $fetch_args = null, $ctor_args = []);

    /**
     * sets where statement from [$column_name => $value, ] to
     * WHERE $column_name = $value AND ...
     *
     * @param array $condition
     * @return QueryInterface
     */
    public function condition(array $condition);

    /**
     * sets the order by clause when select.
     *
     * @param string $order
     * @param string $direction
     * @return QueryInterface
     */
    public function orderBy($order, $direction = 'ASC');

    /**
     * selects and returns as indicated by fetch mode.
     * most likely returns some EntityInterface objects.
     *
     * @param array $keys
     * @return mixed[]|EntityInterface[]
     */
    public function find($keys = []);

    /**
     * execute select statement with $keys as condition.
     * returns PDOStatement reflecting the self::setFetchMode().
     *
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys = []);

    /**
     * returns the number of rows found.
     * the $keys are same as that of self::select method.
     *
     * @param array $keys
     * @return int
     */
    public function count($keys = []);
    
    /**
     * insert a data into a database table.
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data);

    /**
     * returns the last inserted ID.
     *
     * @param string $table
     * @param string $idName
     * @return string
     */
    public function lastId($table = '', $idName = '');

    /**
     * updates the value as $data for rows selected by $keys.
     *
     * @param array $keys
     * @param array $data
     * @return bool
     */
    public function update(array $keys, array $data);

    /**
     * deletes the rows selected by $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function delete($keys);

}