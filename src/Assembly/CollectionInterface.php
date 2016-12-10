<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * sets entities for collection.
     * 
     * @param EntityInterface[] $entities
     */
    public function setEntities($entities);

    /**
     * @return EntityInterface[]
     */
    public function toArray();

    /**
     * saves entities to database. 
     */
    public function save();
    
    /**
     * loads related entities with $name. 
     * the repository must implement a method $repo->$name 
     * that returns RelationInterface object.
     * 
     * @param string $name
     * @return CollectionInterface
     */
    public function load($name);

    /**
     * gets entities from collection based on the $keys condition. 
     * 
     * @param array $keys
     * @return null|EntityInterface
     */
    public function get(array $keys);

    /**
     * gets an entity from collection for the primary key value. 
     * 
     * @param string $id
     * @return null|EntityInterface
     */
    public function getById($id);

    /**
     * get the related entities for the $fromEntity.
     *
     * @param EntityInterface $fromEntity
     * @return Collection
     * @throws \BadMethodCallException
     */
    public function getRelatedEntities($fromEntity);

    /**
     * adds the $entity to a collection. 
     * if relation object is set, the entity is also related
     * to the sourceEntity of the relation object. 
     * 
     * @param EntityInterface $entity
     */
    public function add(EntityInterface $entity);

    /**
     * deletes the $entity from the collection. 
     * if relation object is set, the entity is also removed 
     * from the relation (delete only works for Join relation). 
     * 
     * @param EntityInterface $entity
     */
    public function delete(EntityInterface $entity);

    /**
     * returns a new collection with entities filtered out
     * by $callable returning false. 
     * $callable = function(EntityInterface): bool
     * 
     * @param callable $callable
     * @return CollectionInterface
     */
    public function filter(callable $callable);

    /**
     * applies $callable for all entities.
     * $callable = function(EntityInterface): void
     * 
     * @param callable $callable
     * @return $this
     */
    public function walk(callable $callable);

    /**
     * creates an array from return value of $callable.
     * $callable = function(EntityInterface): mixed
     * 
     * @param callable $callable
     * @return array
     */
    public function map(callable $callable);

    /**
     * returns an array consisted of entity's column value. 
     * 
     * @param string $column
     * @return array
     */
    public function column($column);

    /**
     * reduces into value. 
     * 
     * @param callable $callable
     * @param null|mixed $initial
     * @return mixed|EntityInterface
     */
    public function reduce(callable $callable, $initial = null);

    /**
     * sums the $column value. value must be numeric. 
     * 
     * @param string $column
     * @return int
     */
    public function sum($column);

    /**
     * returns max value of the $column value. value must be numeric.
     * 
     * @param string $column
     * @return int|mixed
     */
    public function max($column);

    /**
     * returns min value of the $column value. value must be numeric.
     * 
     * @param string $column
     * @return int|mixed
     */
    public function min($column);
}