<?php
namespace WScore\Repository;

use WScore\Repository\Relation\HasMany;
use WScore\Repository\Relation\HasOne;
use WScore\Repository\Relation\JoinTo;

class Repo
{
    /**
     * @var RelationInterface[]|JoinRepositoryInterface[]
     */
    private $container;

    /**
     * @param RepositoryInterface $sourceRepo
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return HasOne
     */
    public function hasOne(
        RepositoryInterface $sourceRepo,
        EntityInterface $entity,
        RepositoryInterface $repo,
        $convert = []
    ) {
        return new HasOne($sourceRepo, $repo, $entity, $convert);
    }

    /**
     * @param RepositoryInterface $sourceRepo
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return HasMany
     */
    public function hasMany(
        RepositoryInterface $sourceRepo,
        EntityInterface $entity,
        RepositoryInterface $repo,
        $convert = []
    ) {
        return new HasMany($sourceRepo, $repo, $entity, $convert);
    }

    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $targetRepo
     * @param EntityInterface     $entity
     * @param string|null         $joinTable
     * @param array               $convert
     * @return JoinTo
     */
    public function hasJoin(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        EntityInterface $entity,
        $joinTable = '',
        $convert = []
    ) {
        $joinTable = $joinTable ?: $this->makeJoinTableName($targetRepo, $sourceRepo);
        $join      = $this->container[$joinTable];
        return new JoinTo($sourceRepo, $targetRepo, $join, $entity, $convert);
    }


    /**
     * create a join table name from 2 joined tables.
     * sort table name by alphabetical order.
     *
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $targetRepo
     * @return string
     */
    private function makeJoinTableName(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo
    ) {
        $list = [$targetRepo->getTable(), $sourceRepo->getTable()];
        sort($list);
        return implode('_', $list);
    }
}