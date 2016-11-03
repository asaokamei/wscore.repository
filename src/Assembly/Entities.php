<?php
namespace WScore\Repository\Assembly;

use IteratorAggregate;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class Entities implements IteratorAggregate
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var EntityInterface
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
    public function entities($entities)
    {
        $this->entities = $entities;
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
}