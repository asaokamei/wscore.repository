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
     * @return CollectRelatedInterface
     */
    public function load($name);

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