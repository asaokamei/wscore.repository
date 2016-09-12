<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;

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
     * @return JoinEntityInterface[]
     */
    public function getJoinEntities();
}