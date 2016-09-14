<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

interface JoinRelationInterface extends RelationInterface
{
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