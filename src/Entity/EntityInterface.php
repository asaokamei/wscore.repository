<?php
namespace WScore\Repository\Entity;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;

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
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function setForeignKeys(EntityInterface $entity, $convert = []);

    /**
     * @return bool
     */
    public function save();

    /**
     * @param string $name
     * @param array  $args
     * @return JoinRelationInterface|RelationInterface|mixed
     */
    public function __call($name, $args);

    /**
     * @param string $name
     * @return string|EntityInterface[]
     */
    public function __get($name);

    /**
     * @param string $name
     * @param Collection|EntityInterface[] $entities
     */
    public function setRelatedEntities($name, $entities);

    /**
     * @param string $name
     * @return null|JoinRelationInterface|RelationInterface
     */
    public function getRelationObject($name);

    /**
     * @param string $name
     * @return null|Collection|EntityInterface[]
     */
    public function getRelatedEntities($name);
}