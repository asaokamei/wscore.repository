<?php
namespace WScore\Repository;

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