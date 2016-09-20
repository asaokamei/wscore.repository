<?php
namespace WScore\Repository\Relations;

use InvalidArgumentException;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractJoinRepository implements JoinRepositoryInterface
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var CurrentDateTime
     */
    protected $now;

    /**
     * @var array
     */
    protected $primaryKeys = [];

    /**
     * @Override
     * @var string[]
     */
    protected $columnList = [];

    /**
     * @Override
     * @var string|EntityInterface
     */
    protected $entityClass = Entity::class;

    /**
     * @Override
     * @var string
     */
    protected $from_table;

    protected $from_convert = [];

    /**
     * @Override
     * @var RepositoryInterface
     */
    protected $from_repo;

    /**
     * @Override
     * @var string
     */
    protected $to_table;

    protected $to_convert = [];

    /**
     * @Override
     * @var RepositoryInterface
     */
    protected $to_repo;

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
     * @param EntityInterface $entity
     * @param bool            $getOpposite
     * @return string
     */
    private function getFromOrTo($entity, $getOpposite = false)
    {
        if ($entity->getTable() === $this->from_table) {
            $target = ['from', 'to',];
        } elseif ($entity->getTable() === $this->to_table) {
            $target = ['to', 'from',];
        } else {
            throw new InvalidArgumentException('entity not from nor to table.');
        }
        if ($getOpposite) {
            return $target[1];
        }
        return $target[0];
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    private function getConvert($entity)
    {
        $fromTo = $this->getFromOrTo($entity);
        return $fromTo === 'from' ? $this->from_convert : $this->to_convert;
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    private function getConvertedKeys($entity)
    {
        return HelperMethods::convertDataKeys($entity->getKeys(), $this->getConvert($entity));
    }

    /**
     * returns QueryInterface on join table.
     *
     * @param EntityInterface|null $entity1
     * @param EntityInterface|null $entity2
     * @return QueryInterface
     */
    public function queryJoin($entity1 = null, $entity2 = null)
    {
        $keys = [];
        if ($entity1) {
            $keys = $this->getConvertedKeys($entity1);
        }
        if ($entity2) {
            $keys = array_merge(
                $keys,
                $this->getConvertedKeys($entity2)
            );
        }
        return $this->query
            ->withTable($this->table)
            ->condition($keys)
            ->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass, [$this->table, $this->primaryKeys, []]);
    }

    /**
     * returns QueryInterface on targeted table, opposite of $entity's table.
     *
     * @param EntityInterface $entity
     * @return QueryInterface
     */
    public function queryTarget($entity)
    {
        $fromTo = $this->getFromOrTo($entity, true);
        $method = "query" . $fromTo;
        return $this->$method($entity);
    }

    /**
     * @param EntityInterface|null $entity
     * @return QueryInterface
     */
    protected function queryFrom($entity = null)
    {
        $keys = $entity
            ? $this->getConvertedKeys($entity)
            : [];

        return $this->from_repo
            ->query()
            ->condition($keys)
            ->join($this->table, $this->from_convert);
    }

    /**
     * @param EntityInterface|null $entity
     * @return QueryInterface
     */
    protected function queryTo($entity = null)
    {
        $keys = $entity
            ? $this->getConvertedKeys($entity)
            : [];

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
     * @param EntityInterface $entity
     * @return EntityInterface[]
     */
    public function select($entity)
    {
        return $this->queryTarget($entity)
            ->select()
            ->fetchAll();
    }

    /**
     * @param EntityInterface $entity1
     * @param EntityInterface $entity2
     * @return bool|string
     */
    public function insert($entity1, $entity2)
    {
        $data = array_merge(
            $this->getConvertedKeys($entity1),
            $this->getConvertedKeys($entity2)
        );
        if (!$id = $this->queryJoin()->insert($data)) {
            return false; // failed to insert...
        }
        return $id;
    }

    /**
     * @param EntityInterface      $entity1
     * @param EntityInterface|null $entity2
     * @return bool
     */
    public function delete($entity1, $entity2 = null)
    {
        return $this
            ->queryJoin($entity1, $entity2)
            ->delete([]);
    }
}