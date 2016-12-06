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
    protected $repository;

    /**
     * @var RelationInterface|JoinRelationInterface
     */
    protected $relation;

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
     * @param null|RelationInterface|JoinRelationInterface $relation
     */
    public function __construct($repository, $relation = null)
    {
        $this->repository = $repository;
        $this->relation   = $relation;
    }

    /**
     * @param EntityInterface[] $entities
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;
    }

    /**
     * @return EntityInterface[]
     */
    public function toArray()
    {
        return $this->entities;
    }

    /**
     * 
     */    
    public function save()
    {
        $this->walk(function(EntityInterface $entity) {
            $entity->save();
        });
    }

    /**
     * @param EntityInterface $entity
     */
    public function add(EntityInterface $entity)
    {
        if ($this->relation) {
            $this->relation->relate($entity);
        }
        $this->entities[] = $entity;
    }

    /**
     * @param EntityInterface $entity
     * @throws \InvalidArgumentException
     */
    public function delete(EntityInterface $entity)
    {
        if (!$this->relation) {
            throw new \InvalidArgumentException('no relation set in Collection');
        }
        if (!$this->relation instanceof RelationInterface) {
            throw new \InvalidArgumentException('cannot delete relation');
        }
        $this->relation->delete($entity);
        foreach($this->entities as $idx => $e) {
            if ($e->getKeys() === $entity->getKeys()) {
                unset($this->entities[$idx]);
                break;
            }
        }
    }

    /**
     * @param string $name
     * @return CollectRelatedInterface
     * @throws \InvalidArgumentException
     */
    public function load($name)
    {
        if (array_key_exists($name, $this->related)) {
            return $this->related[$name];
        }
        $relation             = $this->repository->$name();
        $relatedCollection    = $this->forgeRelatedCollection($relation, $this->entities);
        $this->related[$name] = $relatedCollection;

        /**
         * set related entities to the source entities. 
         */
        foreach($this->entities as $entity) {
            $found = $relatedCollection->getRelatedEntities($entity);
            $entity->setRelatedEntities($name, $found);
        }

        return $relatedCollection;
    }

    /**
     * @param $relation
     * @param $entities
     * @return CollectRelatedInterface
     * @throws \InvalidArgumentException
     */
    private function forgeRelatedCollection($relation, $entities)
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
     * @param callable $callable
     * @return CollectionInterface
     */
    public function filter(callable $callable)
    {
        $found = array_filter($this->entities, $callable);

        return $this->repository->newCollection($found);
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function walk(callable $callable)
    {
        array_walk($this->entities, $callable);
        return $this;
    }

    /**
     * @param callable $callable
     * @return array
     */
    public function map(callable $callable)
    {
        return array_map($callable, $this->entities);
    }

    /**
     * @param string $column
     * @return array
     */
    public function column($column)
    {
        return $this->map(function(EntityInterface $entity) use($column) {
            return $entity->get($column);
        });
    }
    
    /**
     * @param callable $callable
     * @param null|mixed $initial
     * @return mixed
     */
    public function reduce(callable $callable, $initial = null)
    {
        return array_reduce($this->entities, $callable, $initial);
    }

    /**
     * @param string $column
     * @return int
     * @throws \InvalidArgumentException
     */
    public function sum($column)
    {
        return $this->reduce(function($sum, EntityInterface $entity) use($column) {
            $value = $entity->get($column);
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException("summation on non numeric column, {$column}.");
            }
            return $sum + $value; 
        }, 0);
    }

    /**
     * @param string $column
     * @return int|mixed
     */
    public function max($column)
    {
        return $this->reduce(function($max, EntityInterface $entity) use($column) {
            $value = $entity->get($column);
            if ($max === null) {
                return $value;
            }
            return $max < $value ? $value : $max;
        }, null);
    }

    /**
     * @param string $column
     * @return int|mixed
     */
    public function min($column)
    {
        return $this->reduce(function($min, EntityInterface $entity) use($column) {
            $value = $entity->get($column);
            if ($min === null) {
                return $value;
            }
            return $min > $value ? $value : $min;
        }, null);
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
     * @return EntityInterface|null
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
        $this->add($value);
    }

    /**
     * Offset to unset
     *
     * @param mixed
     * @return void
     * @throws \InvalidArgumentException
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->entities)) {
            if ($this->relation) {
                $this->delete($this->entities[$offset]);
            }
            if (isset($this->entities[$offset])) {
                // maybe already deleted by delete method...
                unset($this->entities[$offset]);
            }
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