<?php
namespace WScore\Repository\Relations;

use DateTimeImmutable;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractJoinRepository implements JoinRepositoryInterface
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $primaryKeys;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var DateTimeImmutable
     */
    protected $now;

    /**
     * @var RepositoryInterface
     */
    protected $from_repo;

    /**
     * @var array
     */
    protected $from_convert;

    /**
     * @var RepositoryInterface
     */
    protected $to_repo;

    /**
     * @var array
     */
    protected $to_convert;

    /**
     * @var string|EntityInterface
     */
    protected $entityClass = Entity::class;

    /**
     * @var string[]
     */
    protected $columnList;

    /**
     * creates a conversion table for joining tables.
     *
     * @Override
     * @param RepositoryInterface $repo
     * @return array
     */
    protected function makeConversion(RepositoryInterface $repo)
    {
        $table = $repo->getTable();
        $convert = [];
        foreach ($repo->getKeyColumns() as $key) {
            $convert[$key] = $table . '_' . $key;
        }
        return $convert;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return string|EntityInterface
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return string[]
     */
    public function getKeyColumns()
    {
        return $this->primaryKeys;
    }

    /**
     * @return string[]
     */
    public function getColumnList()
    {
        return $this->columnList;
    }

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function create($data)
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass($this->table, $this->primaryKeys, $this->columnList);
        $entity->fill($data);
        return $entity;
    }

    /**
     * returns QueryInterface on join table.
     *
     * @param EntityInterface|null $fromEntity
     * @param EntityInterface|null $toEntity
     * @return QueryInterface
     */
    public function queryJoin($fromEntity = null, $toEntity = null)
    {
        $keys = [];
        if ($fromEntity) {
            $keys = $this->convertFromKeys($fromEntity);
        }
        if ($toEntity) {
            $keys = array_merge(
                $keys,
                $this->convertToKeys($toEntity)
            );
        }
        return $this->query
            ->withTable($this->table)
            ->condition($keys)
            ->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass, [$this->table, $this->primaryKeys, []]);
    }

    /**
     * @param EntityInterface $fromEntity
     * @return array
     */
    public function convertFromKeys(EntityInterface $fromEntity)
    {
        return HelperMethods::convertDataKeys($fromEntity->getKeys(), $this->from_convert);
    }

    /**
     * @param EntityInterface $toEntity
     * @return array
     */
    public function convertToKeys(EntityInterface $toEntity)
    {
        return HelperMethods::convertDataKeys($toEntity->getKeys(), $this->to_convert);
    }

    /**
     * returns QueryInterface on targeted table, opposite of $entity's table.
     *
     * @param EntityInterface $fromEntity
     * @return QueryInterface
     */
    public function queryTarget($fromEntity)
    {
        $keys   = $this->convertFromKeys($fromEntity);
        return $this->to_repo
            ->query()
            ->condition($keys)
            ->join($this->table, $this->to_convert);
    }


    /**
     * @param string $key
     * @return EntityInterface
     */
    public function findByKey($key)
    {
        $primaryKey = $this->primaryKeys[0];
        return $this->queryJoin()
            ->select([$primaryKey => $key])
            ->fetch();
    }

    /**
     * @param EntityInterface $fromEntity
     * @return EntityInterface[]
     */
    public function select($fromEntity)
    {
        return $this->queryTarget($fromEntity)
            ->select()
            ->fetchAll();
    }

    /**
     * @param EntityInterface $fromEntity
     * @param EntityInterface $toEntity
     * @return bool|string
     */
    public function insert($fromEntity, $toEntity)
    {
        $data = array_merge(
            $this->convertFromKeys($fromEntity),
            $this->convertToKeys($toEntity)
        );
        if (!$id = $this->queryJoin()->insert($data)) {
            return false; // failed to insert...
        }
        return $id;
    }

    /**
     * @param EntityInterface      $fromEntity
     * @param EntityInterface|null $toEntity
     * @return bool
     */
    public function delete($fromEntity, $toEntity = null)
    {
        return $this
            ->queryJoin($fromEntity, $toEntity)
            ->delete([]);
    }
}