<?php
namespace WScore\Repository;

interface EntityInterface
{
    /**
     * @param array $data
     * @return static
     */
    public static function create(array $data);

    /**
     * @return array
     */
    public function getPrimaryKeyColumns();

    /**
     * @return array
     */
    public function getKeys();

    /**
     * @return bool
     */
    public function isFetched();

    /**
     * @param string $id
     */
    public function setPrimaryKeyOnCreatedEntity($id);

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