<?php
namespace WScore\Repository;

use Interop\Container\ContainerInterface;
use PDO;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\GenericJoinRepo;
use WScore\Repository\Relations\JoinRepositoryInterface;
use WScore\Repository\Repository\GenericRepository;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\HasOne;
use WScore\Repository\Relations\JoinTo;
use WScore\Repository\Repository\RepositoryInterface;

class Repo
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var mixed[]
     */
    private $repositories = [];

    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * Repo constructor.
     *
     * @param ContainerInterface $container
     * @param PDO|null           $pdo
     */
    public function __construct($container = null, $pdo = null)
    {
        $this->container = $container;
        // use default classes if not set.
        $this->query = $this->_has(QueryInterface::class)
            ? $this->_get(QueryInterface::class)
            : new PdoQuery($pdo ?: $this->_get(PDO::class));
    }

    /**
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return CurrentDateTime
     */
    public function getCurrentDateTime()
    {
        $key = CurrentDateTime::class;
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        return $this->repositories[$key] = new CurrentDateTime();
    }

    /**
     * @param string $key
     * @return RepositoryInterface
     */
    public function getRepository($key)
    {
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        return $this->repositories[$key] = new GenericRepository($this, $key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function _get($key)
    {
        if (isset($this->repositories[$key])) {
            return $this->repositories[$key];
        }
        if ($this->container && $this->container->has($key)) {
            return $this->repositories[$key] = $this->container->get($key);
        }
        return null;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function _has($key)
    {
        if (isset($this->repositories[$key])) {
            return true;
        }
        if ($this->container && $this->container->has($key)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $key
     * @return JoinRepositoryInterface
     */
    public function getJoinRepository($key)
    {
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        return $this->repositories[$key] = new GenericJoinRepo();
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
        $join      = $this->getJoinRepository($joinTable);
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