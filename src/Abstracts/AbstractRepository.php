<?php

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var DaoInterface
     */
    protected $dao;

    /**
     * @var string|EntityInterface
     */
    protected $entityClass;

    /**
     * @param PDOStatement $statement
     * @return EntityInterface[]
     */
    protected function fetchObject(PDOStatement $statement)
    {
        return $statement->fetchObject($this::getEntityClass());
    }

    /**
     * @return string|EntityInterface
     */
    public static function getEntityClass()
    {
        return EntityInterface::class;
    }

    /**
     * @return string[]
     */
    public static function getKeyColumns()
    {
        $class = self::getEntityClass();
        return $class::getPrimaryKeyColumns();
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
        $statement = $this->dao->select($keys);
        if (!$statement) {
            return null;
        }
        return $this->fetchObject($statement);
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
        $statement = $this->dao->select($keys);
        if (!$statement) {
            return null;
        }
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('more than 1 found for findByKey.');
        }
        return $this->fetchObject($statement)[0];
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
        if (!$id = $this->dao->insert($entity->toArray())) {
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
        return $this->dao->update($entity->toArray());
    }

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity)
    {
        return $this->dao->delete($entity->getKeys());
    }

    /**
     * @return DaoInterface
     */
    public function getDao()
    {
        return $this->dao;
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface|null
     */
    public function hasOne(EntityInterface $entity, RepositoryInterface $repo, $convert = [])
    {
        return RelationHelper::hasOne($entity, $repo, $convert);
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface[]
     */
    public function hasMany(EntityInterface $entity, RepositoryInterface $repo, $convert = [])
    {
        return RelationHelper::hasMany($entity, $repo, $convert);
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param string|null         $joinTable
     * @param array               $convert1
     * @param array               $convert2
     * @return EntityInterface[]
     */
    public function hasJoin(
        EntityInterface $entity,
        RepositoryInterface $repo,
        $joinTable = '',
        $convert1 = [],
        $convert2 = []
    ) {
        $joinTable = $joinTable ?: RelationHelper::makeJoinTableName($repo->getDao()->getTable(), $this->getDao()->getTable());
        $statement = RelationHelper::hasJoin($entity, $repo, $joinTable, $convert1, $convert2);
        return $this->fetchObject($statement);
    }
}