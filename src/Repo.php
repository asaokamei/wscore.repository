<?php
namespace WScore\Repository;

use Interop\Container\ContainerInterface;
use WScore\Repository\Generic\Repository;
use WScore\Repository\Relation\HasMany;
use WScore\Repository\Relation\HasOne;
use WScore\Repository\Relation\JoinTo;

class Repo
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RepositoryInterface[]
     */
    private $repositories = [];

    /**
     * Repo constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param string $key
     * @return RepositoryInterface
     */
    public function get($key)
    {
        if (isset($this->repositories[$key])) {
            return $this->repositories[$key];
        }
        if ($this->container->has($key)) {
            return $this->repositories[$key] = $this->container->get($key);
        }
        $table = strpos($key, '\\') === false ? $key : (substr($key, strrpos($key, '\\') + 1));
        if (isset($this->repositories[$table])) {
            return $this->repositories[$table];
        }
        return $this->repositories[$table] = new Repository($this, $table);
    }
    
    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $repo
     * @param EntityInterface     $entity
     * @param array               $convert
     * @return HasOne
     */
    public function hasOne(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $repo,
        EntityInterface $entity,
        $convert = []
    ) {
        return new HasOne($sourceRepo, $repo, $entity, $convert);
    }

    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $repo
     * @param EntityInterface     $entity
     * @param array               $convert
     * @return HasMany
     */
    public function hasMany(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $repo,
        EntityInterface $entity,
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
        $join      = $this->get($joinTable);
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