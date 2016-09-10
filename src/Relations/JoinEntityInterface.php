<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;

interface JoinEntityInterface extends EntityInterface
{
    /**
     * @return array
     */
    public function getKeysFrom();

    /**
     * @return array
     */
    public function getKeysTo();
}