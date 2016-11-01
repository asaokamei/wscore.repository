<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

interface JoinRelationInterface extends RelationInterface
{
    /**
     * get keys to find join-entities from $fromEntity.
     *
     * @param EntityInterface $fromEntity
     * @return array
     */
    public function convertFromKeys(EntityInterface $fromEntity);

    /**
     * @return bool
     */
    public function clear();

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity);

    /**
     * @param null|EntityInterface $entity
     * @return QueryInterface
     */
    public function queryJoin($entity = null);
}