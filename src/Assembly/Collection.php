<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class Collection implements CollectionInterface
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
     * @var CollectJoin[]|CollectHasSome[]
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
     * @param array $key
     */
    public function find(array $key)
    {
        $this->entities = $this->repository->find($key);
    }

    /**
     * @param array|string $key
     */
    public function findByKey($key)
    {
        if ($entity = $this->repository->findByKey($key)) {
            $this->entities = [$entity];
        }
    }

    /**
     * @param string $name
     * @return CollectRelatedInterface
     */
    public function load($name)
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
            $found = $related->getRelatedEntities($entity);
            $entity->setRelatedEntities($name, $found);
        }

        return $related;
    }

    /**
     * @param $relation
     * @param $entities
     * @return CollectRelatedInterface
     */
    private function getLoaded($relation, $entities)
    {
        if ($relation instanceof JoinRelationInterface) {
            $related = CollectJoin::forge($relation->getTargetRepository(), $relation, $entities);
        } elseif ($relation instanceof RelationInterface) {
            $related = CollectHasSome::forge($relation->getTargetRepository(), $relation, $entities);
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
        if (is_null($offset)) {
            $this->entities[] = $value;
        } else {
            $this->entities[$offset] = $value;
        }
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