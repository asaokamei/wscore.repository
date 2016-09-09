<?php
namespace WScore\Repository\Abstracts;

use InvalidArgumentException;
use WScore\Repository\EntityInterface;
use WScore\Repository\QueryInterface;
use WScore\Repository\RepositoryInterface;

/* abstract */ class AbstractRepository implements RepositoryInterface
{
    /**
     * @var QueryInterface
     */
    protected $dao;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string[]
     */
    private $primaryKeys = [];

    /**
     * @var string[]
     */
    private $columnList = [];

    /**
     * @var string|EntityInterface
     */
    protected $entityClass;

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
     * @param array $data
     * @return EntityInterface
     */
    public function create($data)
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass($this->primaryKeys, $this->columnList);
        $entity->fill($data);
        return $entity;
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
     * @return string
     */
    protected function getKeyColumnName()
    {
        $keys = $this->getKeyColumns();
        if (count($keys) !== 1) {
            throw new InvalidArgumentException('more than 1 primary key.');
        }
        return $keys[0];
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys)
    {
        $statement = $this->query()->select($keys);
        if (!$statement) {
            return null;
        }
        return $statement->fetchAll();
    }

    /**
     * @param array|string $keys
     * @return EntityInterface|null
     */
    public function findByKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$this->getKeyColumnName() => $keys];
        }
        $statement = $this->query()->select($keys);
        if (!$statement) {
            return null;
        }
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('more than 1 found for findByKey.');
        }

        return $statement->fetch();
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function save(EntityInterface $entity)
    {
        if ($entity->isFetched()) {
            $this->update($entity);
        } else {
            $entity = $this->insert($entity);
        }
        return $entity;
    }

    /**
     * for auto-increment table, this method returns a new entity
     * with the new id.
     *
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function insert(EntityInterface $entity)
    {
        if (!$id = $this->query()->insert($entity->toArray())) {
            return null;
        }
        if ($id !== true) {
            $entity->setPrimaryKeyOnCreatedEntity($id);
        }

        return $entity;
    }

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function update(EntityInterface $entity)
    {
        return $this->query()->update($entity->getKeys(), $entity->toArray());
    }

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity)
    {
        return $this->query()->delete($entity->getKeys());
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        return $this->dao
            ->withTable($this->table)
            ->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass, [$this->primaryKeys, $this->columnList]);
    }
}