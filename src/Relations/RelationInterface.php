<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

interface RelationInterface
{
    /**
     * returns target repository.
     * 
     * @return RepositoryInterface
     */
    public function getTargetRepository();

    /**
     * retrieves keys from a from-entity to query the target repository.
     * 
     * @param EntityInterface $entity
     * @return array
     */
    public function getTargetKeys(EntityInterface $entity);
    
    /**
     * sets from-entity to query target repository. 
     *
     * @param EntityInterface[] ...$sourceEntity
     * @return AbstractRelation
     */
    public function withEntity(EntityInterface ...$sourceEntity);

    /**
     * @param array $key
     * @return static
     */
    public function setCondition(array $key);
    
    /**
     * returns an QueryInterface object to query target repository.
     * 
     * @return QueryInterface
     */
    public function query();

    /**
     * searches target repository. 
     * if an entity is set by withEntity, searches for entities related to the entity.
     * 
     * @param array $keys
     * @return Collection
     */
    public function collect($keys = []);

    /**
     * count the related entities.
     * 
     * @return int
     */
    public function count();

    /**
     * relate an entity to the source entity.
     * 
     * @param EntityInterface $targetEntity
     */
    public function relate(EntityInterface $targetEntity);
}