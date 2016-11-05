<?php
namespace WScore\Repository\Assembly;

use IteratorAggregate;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class EntityList implements IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var EntityInterface[]
     */
    private $entities = [];

    /**
     * @var Joined[]|Related[]
     */
    private $related = [];

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param EntityInterface[] $entities
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;
    }

    /**
     * @param string $sql
     * @param array $data
     */
    public function execute($sql, $data = [])
    {
        $stmt = $this->repository->query()->execute($sql, $data);
        $this->entities = $stmt->fetchAll();
    }

    /**
     * @param string $name
     * @return Joined|Related
     */
    public function relate($name)
    {
        if (array_key_exists($name, $this->related)) {
            return $this->related[$name];
        }
        $relation             = $this->repository->$name();
        $related              = $this->getLoaded($relation, $this->entities);
        $this->related[$name] = $related;

        /**
         * set related entities to the source entities. 
         */
        foreach($this->entities as $entity) {
            $found = $related->find($entity);
            $entity->setRelatedEntities($name, $found);
        }

        return $related;
    }

    /**
     * @param $relation
     * @param $entities
     * @return Joined|Related
     */
    private function getLoaded($relation, $entities)
    {
        if ($relation instanceof JoinRelationInterface) {
            $related = Joined::forge($relation->getTargetRepository(), $relation, $entities);
        } elseif ($relation instanceof RelationInterface) {
            $related = Related::forge($relation->getTargetRepository(), $relation, $entities);
        } else {
            throw new \InvalidArgumentException();
        }

        return $related;
    }

    /**
     * use generator as iterator.
     */
    public function getIterator()
    {
        foreach ($this->entities as $entity) {
            yield $entity;
        }
    }

    /**
     * Whether a offset exists
     *
     * @param mixed 
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->entities);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed 
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return array_key_exists($offset, $this->entities) ?
            $this->entities[$offset] : null;
    }

    /**
     * Offset to set
     *
     * @param mixed
     * @param mixed
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->entities[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @param mixed
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->entities)) {
            unset($this->entities[$offset]);
        }
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->entities);
    }
}