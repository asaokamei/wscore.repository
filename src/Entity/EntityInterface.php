<?php
namespace WScore\Repository\Entity;

interface EntityInterface
{

    /**
     * @return string
     */
    public function getTable();
    
    /**
     * @return array
     */
    public function getKeyColumns();

    /**
     * @return array
     */
    public function getKeys();

    /**
     * @return string
     */
    public function getIdValue();

    /**
     * @return string
     */
    public function getIdName();

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
     * @return array
     */
    public function getUpdatedData();

    /**
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function relate(EntityInterface $entity, $convert = []);

    /**
     * @return bool
     */
    public function save();
}