<?php
namespace WScore\Repository\Repository;

use DateTimeImmutable;
use InvalidArgumentException;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var Repo
     */
    protected $repo;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var DateTimeImmutable
     */
    protected $now;

    /**
     * @Override
     * @var string
     */
    protected $table;

    /**
     * @Override
     * @var string[]
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
     * @var string[]
     */
    protected $timeStamps = [
        'created_at' => null,
        'updated_at' => null,
    ];

    /**
     * @Override
     * @var string
     */
    protected $timeStampFormat = 'Y-m-d H:i:s';

    /**
     * @Override
     * @var bool
     */
    protected $useAutoInsertId = false;

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
    public function create(array $data)
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass($this->table, $this->primaryKeys, $this);
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
     * @param array $data
     * @return array
     */
    protected function filterDataByColumns(array $data)
    {
        if (!$columns = $this->getColumnList()) {
            return $data;
        }
        return HelperMethods::filterDataByKeys($data, $columns);
    }
    
    /**
     * @return string
     */
    protected function getIdName()
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
    public function find(array $keys)
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
            $keys = [$this->getIdName() => $keys];
        }
        $statement = $this->query()->select($keys);
        $entity    = $statement->fetch();
        if ($statement->fetch()) {
            throw new InvalidArgumentException('more than 1 found for findByKey.');
        }

        return $entity;
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
        $data = $entity->toArray();
        $data = $this->filterDataByColumns($data);
        $data = $this->_addTimeStamps($data, 'created_at');
        $data = $this->_addTimeStamps($data, 'updated_at');
        if (!$id = $this->query()->insert($data)) {
            return null;
        }
        if ($this->useAutoInsertId) {
            $id = $this->query()->lastId($this->getTable(), $this->getIdName());
        }
        $entity->setPrimaryKeyOnCreatedEntity($id);

        return $entity;
    }

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function update(EntityInterface $entity)
    {
        $data = $entity->toArray();
        $data = $this->filterDataByColumns($data);
        $data = HelperMethods::removeDataByKeys($data, $this->getKeyColumns());
        $data = $this->_addTimeStamps($data, 'updated_at');
        return $this->query()->update($entity->getKeys(), $data);
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
        return $this->query
            ->withTable($this->table)
            ->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass, [$this->table, $this->primaryKeys, $this]);
    }

    /**
     * @param array  $data
     * @param string $type
     * @return array
     */
    protected function _addTimeStamps(array $data, $type)
    {
        if (!isset($this->timeStamps[$type])) {
            return $data;
        }
        $column = $this->timeStamps[$type];
        if (!$this->now) {
            $this->now = new DateTimeImmutable();
        }
        $data[$column] = $this->now->format($this->timeStampFormat);

        return $data;
    }
}