<?php
namespace WScore\Repository\Relations;

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