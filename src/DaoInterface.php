<?php

interface DaoInterface
{
    /**
     * @return string
     */
    public function getTable();

    /**
     * @return mixed
     */
    public function query();

    /**
     * @return array
     */
    public function listColumns();

    /**
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys);
    
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