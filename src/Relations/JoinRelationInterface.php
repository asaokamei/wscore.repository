<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

interface JoinRelationInterface
{
    /**
     * get keys from source entity to query join repository. 
     * 
     * @param EntityInterface $sourceEntity
     * @return array
     */
    public function getJoinKeys(EntityInterface $sourceEntity);
    
    /**
     * deletes all join records. 
     * 
     * @return bool
     */
    public function clear();

    /**
     * un-relate target entity, i.e. deletes a join entity. 
     * must set a sourceEntity using withEntity before. 
     * 
     * @param EntityInterface $targetEntity
     * @return bool
     */
    public function delete(EntityInterface $targetEntity);

    /**
     * query join repository. 
     * 
     * @return QueryInterface
     */
    public function queryJoin();

    /**
     * query target repository. 
     *
     * @param EntityInterface[] ...$joinEntities
     * @return QueryInterface
     */
    public function queryTarget(...$joinEntities);
}