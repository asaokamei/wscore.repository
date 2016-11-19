<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

interface CollectRelatedInterface extends CollectionInterface 
{
    /**
     * @param RepositoryInterface   $repository
     * @param JoinRelationInterface $relation
     * @param EntityInterface[]     $fromEntities
     * @return CollectJoin
     */
    public static function forge($repository, $relation, array $fromEntities);

    /**
     * get the related entities for the $fromEntity.
     * 
     * @param EntityInterface $fromEntity
     * @return EntityInterface[]
     */
    public function getRelatedEntities($fromEntity);
}