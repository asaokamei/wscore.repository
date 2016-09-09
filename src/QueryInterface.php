<?php
namespace WScore\Repository;

use PDOStatement;

interface QueryInterface
{
    /**
     * @param string $table
     * @return QueryInterface
     */
    public function withTable($table);

    /**
     * @return string
     */
    public function getTable();

    /**
     * @param array $condition
     * @return QueryInterface
     */
    public function condition(array $condition);
    
    /**
     * @param string $order
     * @return QueryInterface
     */
    public function orderBy($order);

    /**
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys = []);

    /**
     * @return int
     */
    public function count();
    
    /**
     * insert a data into a database table.
     * returns auto-increment id (or true if no such column).
     *
     * @param array $data
     * @return string|bool
     */
    public function insert(array $data);

    /**
     * @param array $data
     * @return bool
     */
    public function update(array $data);

    /**
     * @param array $keys
     * @return bool
     */
    public function delete($keys);

    /**
     * @param string $join
     * @param array  $keys
     * @param array  $convert1
     * @param array  $convert2
     * @return PDOStatement
     */
    public function join($join, $keys, $convert1 = [], $convert2 = []);
}