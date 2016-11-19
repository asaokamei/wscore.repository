<?php
namespace WScore\Repository\Repository;

use DateTimeImmutable;
use InvalidArgumentException;
use WScore\Repository\Assembly\Collection;
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
     * @var string          table name
     */
    protected $table;

    /**
     * @Override
     * @var string[]        primary keys in array
     */
    protected $primaryKeys = [];

    /**
     * @Override
     * @var string[]        list of columns for filtering input data by keys
     */
    protected $columnList = [];

    /**
     * @Override
     * @var string|EntityInterface      entity class name
     */
    protected $entityClass = Entity::class;

    /**
     * @Override
     * @var string[]        timestamps column at create/update if any
     */
    protected $timeStamps = [
        'created_at' => null,  // sets datetime at creation
        'updated_at' => null,  // sets datetime at modification
    ];

    /**
     * @Override
     * @var string          format of datetime column
     */
    protected $timeStampFormat = 'Y-m-d H:i:s';

    /**
     * @Override
     * @var bool             set to true for auto-increment id
     */
    protected $useAutoInsertId = false;

    /**
     * AbstractRepository constructor.
     *
     * @param Repo $repo
     */
    public function __construct(Repo $repo)
    {
        $this->repo = $repo;
        if (!$this->query) {
            $this->query = $repo->getQuery();
        }
        if (!$this->now) {
            $this->now = $repo->getCurrentDateTime();
        }
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
     * get argument for entity's constructor argument
     * 
     * @override
     * @return array
     */
    protected function getEntityCtorArgs()
    {
        return [$this->table, $this->primaryKeys, $this];
    }

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function create(array $data)
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass(...$this->getEntityCtorArgs());
        $entity->fill($data);
        return $entity;
    }

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function createAsFetched(array $data)
    {
        /** @var EntityInterface $entity */
        $reflection = new \ReflectionClass($this->entityClass);
        $entity     = $reflection->newInstanceWithoutConstructor();
        foreach ($data as $key => $value) {
            $entity->$key = $value;
        }
        $reflection->getConstructor()
            ->invoke($entity, ...$this->getEntityCtorArgs());

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
     * @param array $keys
     * @return Collection
     */
    public function collect(array $keys)
    {
        $entities = $this->find($keys);
        $list     = new Collection($this);
        $list->setEntities($entities);
        
        return $list;
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
        $data = $entity->toArray();
        $data = $this->filterDataByColumns($data);
        $data = HelperMethods::removeDataByKeys($data, $this->getKeyColumns());
        $data = $this->_addTimeStamps($data, 'updated_at');
        return $this->query()->update($entity->getKeys(), $data);
    }

    /**
     * @override
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
            ->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass, $this->getEntityCtorArgs());
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