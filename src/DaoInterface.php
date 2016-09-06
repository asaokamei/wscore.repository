<?php

interface DaoInterface
{
    /**
     * @return string
     */
    public function table();

    /**
     * @return mixed
     */
    public function query();

    /**
     * @return Pdo
     */
    public function getPdo();

    /**
     * @return array
     */
    public function listColumns();

    /**
     * @param array $keys
     * @return PDOStatement
     */
    public function read($keys);
    
    /**
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
}