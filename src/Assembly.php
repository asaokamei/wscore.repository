<?php
namespace WScore\Repository;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

/**
 * Assembly finds related entities. 
 * Use this instead of eager loading related entities. 
 *
 * @package WScore\Repository\Query
 */
class Assembly
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var RelationInterface
     */
    private $relations = [];

    /**
     * list of related entities, organized by relation $name.
     * $this->related[$name] = [EntityInterface, ...]
     *
     * @var EntityInterface[string][]
     */
    private $relatedEntities = [];

    /**
     * Assembly constructor.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string                 $name
     * @param EntityInterface[]      $entities
     * @param RelationInterface|null $relation
     */
    public function setRelated($name, $entities, $relation = null)
    {
        $this->relatedEntities[$name] = $entities;
        if ($relation) {
            $this->relations[$name] = $relation;
        }
    }

    /**
     * @param string          $name
     * @param EntityInterface $entity
     * @return EntityInterface[]
     */
    public function get($name, $entity)
    {
        /** @var RelationInterface $relation */
        $relation = isset($this->relations[$name]) 
            ? $this->relations[$name] : $this->repository->$name();
        $keys = $relation->getTargetKeys($entity);
        
        return $this->findEntitiesFromKeys($keys, $this->relatedEntities[$name]);
    }

    /**
     * @param array             $keys
     * @param EntityInterface[] $entities
     * @return EntityInterface[]
     */
    public function findEntitiesFromKeys($keys, $entities)
    {
        $find  = function(EntityInterface $entity) use ($keys) {
            $data = $entity->toArray();
            foreach($keys as $k => $v) {
                if(!array_key_exists($k, $data)) {
                    return false;
                }
                if((string)$v !== (string) $data[$k]) {
                    return false;
                }
            }
            return true;
        };
        
        $found = [];
        foreach($entities as $entity) {
            if ($find($entity)) {
                $found[] = $entity;
            }
        }
        return $found;
    }
}