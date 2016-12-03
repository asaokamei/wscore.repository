<?php
namespace WScore\Repository\Entity;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;

interface EntityInterface
{

    /**
     * returns table name.
     * 
     * @return string
     */
    public function getTable();
    
    /**
     * returns primary key column names in array. 
     * 
     * @return array
     */
    public function getKeyColumns();

    /**
     * returns primary key values in array. 
     * 
     * @return array
     */
    public function getKeys();

    /**
     * returns primary key value if entity has only one key. 
     * 
     * @return string
     */
    public function getIdValue();

    /**
     * returns primary key column name if entity has only one key.
     * 
     * @return string
     */
    public function getIdName();

    /**
     * returns if the entity is fetched from database. 
     * 
     * @return bool
     */
    public function isFetched();

    /**
     * sets primary key value for auto-inserted id. 
     * 
     * @param string $id
     */
    public function setPrimaryKeyOnCreatedEntity($id);

    /**
     * returns column value. 
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key);
    
    /**
     * fills the entity with the $data value. 
     * 
     * @param array $data
     * @return EntityInterface
     */
    public function fill(array $data);
    
    /**
     * returns raw entity data. 
     * 
     * @return array
     */
    public function toArray();

    /**
     * sets foreign key values to entity. 
     * 
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function setForeignKeys(EntityInterface $entity, $convert = []);

    /**
     * saves the entity to database. 
     * 
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
     * @return null|string|Collection|EntityInterface[]
     */
    public function __get($name);

    /**
     * sets related entities (as Collection) for relation $name,
     * to be retrieved by getRelatedEntities.
     * 
     * @param string $name
     * @param Collection|EntityInterface[] $entities
     */
    public function setRelatedEntities($name, $entities);

    /**
     * gets relation object from repository's $name method. 
     * 
     * @param string $name
     * @return null|JoinRelationInterface|RelationInterface
     */
    public function getRelationObject($name);

    /**
     * returns related entities as Collection. 
     * returns collection set by setRelatedEntities or lazy-loads 
     * using relation object from repository's $name method. 
     * 
     * @param string $name
     * @return null|Collection|EntityInterface[]
     */
    public function getRelatedEntities($name);
}