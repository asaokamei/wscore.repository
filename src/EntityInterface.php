<?php

interface EntityInterface
{
    /**
     * @param array $data
     * @return self
     */
    public static function create(array $data);

    /**
     * @return array
     */
    public static function getPrimaryKeyColumns();

    /**
     * @return array
     */
    public static function listColumns();
    
    /**
     * @return array
     */
    public function getKeys();

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);
    
    /**
     * @param array $data
     * @return EntityInterface
     */
    public function fill(array $data);
    
    /**
     * @return array
     */
    public function toArray();

    /**
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function relate(EntityInterface $entity, $convert = []);
}