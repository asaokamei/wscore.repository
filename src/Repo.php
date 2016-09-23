<?php
namespace WScore\Repository;

use Interop\Container\ContainerInterface;
use PDO;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Query\AuraQuery;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\JoinRepository;
use WScore\Repository\Relations\JoinRepositoryInterface;
use WScore\Repository\Repository\Repository;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\HasOne;
use WScore\Repository\Relations\HasJoin;
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
     * @var null|PDO
     */
    private $pdo;

    /**
     * Repo constructor.
     *
     * @param ContainerInterface $container
     * @param PDO|null           $pdo
     */
    public function __construct($container = null, $pdo = null)
    {
        $this->container = $container;
        $this->pdo       = $pdo;
    }

    /**
     * @return QueryInterface
     */
    public function getQuery()
    {
        if ($this->_has(QueryInterface::class)) {
            return $this->_get(QueryInterface::class);
        }
        return $this->repositories[QueryInterface::class] 
            = new AuraQuery($this->pdo ?: $this->_get(PDO::class));
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
     * @param array  $primaryKeys
     * @param bool   $autoIncrement
     * @return RepositoryInterface
     */
    public function getRepository($key, $primaryKeys = [], $autoIncrement = false)
    {
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        return $this->repositories[$key] = new Repository($this, $key, $primaryKeys, $autoIncrement);
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
     * @param null|string|RepositoryInterface   $sourceRepo
     * @param null|string|RepositoryInterface   $targetRepo
     * @return JoinRepositoryInterface
     */
    public function getJoinRepository($key, $sourceRepo = null, $targetRepo = null)
    {
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($targetRepo)) {
            $targetRepo = $this->getRepository($targetRepo);
        }
        return $this->repositories[$key] = new JoinRepository($this, $key, $sourceRepo, $targetRepo);
    }
    
    /**
     * @param RepositoryInterface|string $sourceRepo
     * @param RepositoryInterface|string $repo
     * @param EntityInterface     $entity
     * @param array               $convert
     * @return HasOne
     */
    public function hasOne(
        $sourceRepo,
        $repo,
        EntityInterface $entity,
        $convert = []
    ) {
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($repo)) {
            $repo = $this->getRepository($repo);
        }
        return new HasOne($sourceRepo, $repo, $entity, $convert);
    }

    /**
     * @param RepositoryInterface|string $sourceRepo
     * @param RepositoryInterface|string $repo
     * @param EntityInterface     $entity
     * @param array               $convert
     * @return HasMany
     */
    public function hasMany(
        $sourceRepo,
        $repo,
        EntityInterface $entity,
        $convert = []
    ) {
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($repo)) {
            $repo = $this->getRepository($repo);
        }
        return new HasMany($sourceRepo, $repo, $entity, $convert);
    }

    /**
     * @param RepositoryInterface|string $sourceRepo
     * @param RepositoryInterface|string $targetRepo
     * @param EntityInterface     $entity
     * @param string|null         $joinTable
     * @return HasJoin
     */
    public function hasJoin(
        $sourceRepo,
        $targetRepo,
        EntityInterface $entity,
        $joinTable = ''
    ) {
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($targetRepo)) {
            $targetRepo = $this->getRepository($targetRepo);
        }
        $joinTable = $joinTable ?: $this->makeJoinTableName($targetRepo, $sourceRepo);
        $join      = $this->getJoinRepository($joinTable, $sourceRepo, $targetRepo);
        return new HasJoin($join, $entity);
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