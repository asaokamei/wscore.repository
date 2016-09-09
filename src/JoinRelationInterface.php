<?php
namespace WScore\Repository;

interface JoinRelationInterface extends RelationInterface
{
    /**
     * @return bool
     */
    public function clear();

    /**
     * @return JoinEntityInterface[]
     */
    public function getJoinEntities();
}