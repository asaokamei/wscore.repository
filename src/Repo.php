<?php
namespace WScore\Repository;

use DateTimeImmutable;
use Interop\Container\ContainerInterface;
use PDO;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repository\Repository;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\BelongsTo;
use WScore\Repository\Repository\RepositoryInterface;
use WScore\Repository\Repository\RepositoryOptions;

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
            = new PdoQuery($this->pdo ?: $this->_get(PDO::class));
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCurrentDateTime()
    {
        $key = DateTimeImmutable::class;
        if ($this->_has($key)) {
            return $this->_get($key);
        }
        return $this->repositories[$key] = CurrentDateTime::forge();
    }

    /**
     * @param string $tableName
     * @param array  $primaryKeys
     * @param bool   $autoIncrement
     * @param null|RepositoryOptions   $options
     * @return RepositoryInterface
     */
    public function getRepository($tableName, $primaryKeys = [], $autoIncrement = false, $options = null)
    {
        if ($this->_has($tableName)) {
            return $this->_get($tableName);
        }
        if (!$options) {
            $options = new RepositoryOptions();
        }
        $options->table           = $tableName;
        $options->primaryKeys     = $primaryKeys ?: ["{$tableName}_id"];
        $options->useAutoInsertId = $autoIncrement;
        $this->repositories[$tableName]
            = new Repository($this, $this->getQuery(), $this->getCurrentDateTime(), $options);

        return $this->repositories[$tableName];
    }

    /**
     * @param string|RepositoryInterface $repository
     * @return Collection
     */
    public function getCollection($repository)
    {
        if (is_string($repository)) {
            $repository = $this->getRepository($repository);
        }
        return new Collection($repository);
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
     * @param RepositoryInterface|string $sourceRepo
     * @param RepositoryInterface|string $repo
     * @param array               $convert
     * @return BelongsTo
     */
    public function belongsTo(
        $sourceRepo,
        $repo,
        $convert = []
    ) {
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($repo)) {
            $repo = $this->getRepository($repo);
        }
        return new BelongsTo($sourceRepo, $repo, $convert);
    }

    /**
     * @param RepositoryInterface|string $sourceRepo
     * @param RepositoryInterface|string $repo
     * @param array               $convert
     * @return HasMany
     */
    public function hasMany(
        $sourceRepo,
        $repo,
        $convert = []
    ) {
        if (is_string($sourceRepo)) {
            $sourceRepo = $this->getRepository($sourceRepo);
        }
        if (is_string($repo)) {
            $repo = $this->getRepository($repo);
        }
        return new HasMany($sourceRepo, $repo, $convert);
    }

    /**
     * @param RepositoryInterface|string $fromRepo
     * @param RepositoryInterface|string $toRepo
     * @param string $joinRepo
     * @param array  $from_convert
     * @param array  $to_convert
     * @return Join
     */
    public function join(
        $fromRepo,
        $toRepo,
        $joinRepo = '',
        $from_convert = [],
        $to_convert = []
    ) {
        if (is_string($fromRepo)) {
            $fromRepo = $this->getRepository($fromRepo);
        }
        if (is_string($toRepo)) {
            $toRepo = $this->getRepository($toRepo);
        }
        if (!$joinRepo) {
            $joinRepo = $this->makeJoinTableName($toRepo, $fromRepo);
        }
        if (is_string($joinRepo)) {
            $joinRepo = $this->getRepository($joinRepo);
        }
        return new Join($fromRepo, $toRepo, $joinRepo, $from_convert, $to_convert);
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