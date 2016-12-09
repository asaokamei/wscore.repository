<?php
namespace WScore\Repository;

use DateTimeImmutable;
use Interop\Container\ContainerInterface;
use PDO;
use WScore\Repository\Helpers\ContainerTrait;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Helpers\Transaction;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repository\Repository;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\BelongsTo;
use WScore\Repository\Repository\RepositoryInterface;
use WScore\Repository\Repository\RepositoryOptions;

class Repo implements ContainerInterface
{
    use ContainerTrait;

    /**
     * Repo constructor.
     *
     * @param PDO|null           $pdo
     */
    public function __construct($pdo = null)
    {
        if ($pdo) {
            $this->set(PDO::class, $pdo);
        }
    }

    /**
     * @param array $list
     * @return Transaction
     */
    public function transaction(...$list)
    {
        if (empty($list)) {
            $list = [$this->get(PDO::class)];
        }
        foreach($list as $idx => $pdo) {
            if (is_string($pdo)) {
                $list[$idx] = $this->get($pdo);
            }
        }
        return new Transaction($list);
    }

    /**
     * @return QueryInterface
     */
    public function getQuery()
    {
        if (!$this->has(QueryInterface::class)) {
            $this->set(
                QueryInterface::class,
                new PdoQuery($this->get(PDO::class))
            );
        }
        return $this->get(QueryInterface::class);
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCurrentDateTime()
    {
        $key = DateTimeImmutable::class;
        if (!$this->has($key)) {
            $this->set($key, CurrentDateTime::forge());
        }
        return $this->get($key);
    }

    /**
     * @param string $tableName
     * @param array  $primaryKeys
     * @param bool   $autoIncrement
     * @param null|RepositoryOptions   $options
     * @return RepositoryInterface
     */
    public function getRepository($tableName, array $primaryKeys = [], $autoIncrement = false, $options = null)
    {
        if (!$this->has($tableName)) {
            $this->set(
                $tableName,
                makeGenericRepository($this, $tableName, $primaryKeys, $autoIncrement, $options)
            );
        }
        return $this->get($tableName);
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
        array $convert = []
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
        array $convert = []
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
     * @param RepositoryInterface|string $joinRepo
     * @param array  $from_convert
     * @param array  $to_convert
     * @return Join
     */
    public function join(
        $fromRepo,
        $toRepo,
        $joinRepo = '',
        array $from_convert = [],
        array $to_convert = []
    ) {
        if (is_string($fromRepo)) {
            $fromRepo = $this->getRepository($fromRepo);
        }
        if (is_string($toRepo)) {
            $toRepo = $this->getRepository($toRepo);
        }
        if (!$joinRepo) {
            $joinRepo = makeJoinTableName($toRepo, $fromRepo);
        }
        if (is_string($joinRepo)) {
            $joinRepo = $this->getRepository($joinRepo);
        }
        return new Join($fromRepo, $toRepo, $joinRepo, $from_convert, $to_convert);
    }
}

/**
 * create a join table name from 2 joined tables.
 * sort table name by alphabetical order.
 *
 * @param RepositoryInterface $sourceRepo
 * @param RepositoryInterface $targetRepo
 * @return string
 */
function makeJoinTableName(
    RepositoryInterface $sourceRepo,
    RepositoryInterface $targetRepo
) {
    $list = [$targetRepo->getTable(), $sourceRepo->getTable()];
    sort($list);
    return implode('_', $list);
}


/**
 * @param Repo   $repo
 * @param string $tableName
 * @param array  $primaryKeys
 * @param bool   $autoIncrement
 * @param null|RepositoryOptions   $options
 * @return Repository
 */
function makeGenericRepository($repo, $tableName, array $primaryKeys = [], $autoIncrement = false, $options = null)
{
    if (!$options) {
        $options = new RepositoryOptions();
    }
    $options->table           = $tableName;
    $options->primaryKeys     = $primaryKeys ?: ["{$tableName}_id"];
    $options->useAutoInsertId = $autoIncrement;

    return new Repository($repo, $repo->getQuery(), $repo->getCurrentDateTime(), $options);
}