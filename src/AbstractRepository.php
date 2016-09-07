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
        return $statement->fetchObject($this->entityClass);
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
        $class = $this->entityClass;
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
     * @param string|array $keys
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
        if ($id === true) {
            return $entity;
        }
        return $this->findByKey([$this->getKeyColumnName() => $id]);
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
        $targetKeys = $repo->getKeyColumns();
        $sourceData = $entity->toArray();
        $keys       = [];
        foreach ($targetKeys as $key) {
            $sourceColumn = isset($convert[$key]) ? $convert[$key] : $key;
            $keys[$key]   = $sourceData[$sourceColumn];
        }
        $found = $repo->find($keys);
        if ($found) {
            return $found[0];
        }
        return null;
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface[]
     */
    public function hasMany(EntityInterface $entity, RepositoryInterface $repo, $convert = [])
    {
        $keys = $entity->getKeys();
        foreach ($convert as $key => $col) {
            $keys[$col] = $keys[$key];
            unset($keys[$key]);
        }
        return $repo->find($keys);
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
        // create the join-table name if not given.
        if (!$joinTable) {
            // two tables in alphabetical order joined with '_'.
            $list = [$repo->getDao()->getTable(), $this->getDao()->getTable()];
            sort($list);
            $joinTable = $joinTable ?: implode('_', $list);
        }
        $statement = $repo->getDao()->join($joinTable, $entity->getKeys(), $convert1, $convert2);
        return $this->fetchObject($statement);
    }
}