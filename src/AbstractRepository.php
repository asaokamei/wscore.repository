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
     * @return string|EntityInterface
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string|array $keys
     * @return EntityInterface[]
     */
    public function find($keys)
    {
        $data = $this->dao->read($keys);
        if (!$data) {
            return null;
        }
        return $data->fetchObject($this->entityClass);
    }

    /**
     * @param EntityInterface $entity
     * @return bool|string
     */
    public function insert(EntityInterface $entity)
    {
        return $this->dao->insert($entity->toArray());
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
        $targetClass = $repo->getEntityClass();
        $targetKeys  = $targetClass::getPrimaryKeyColumns();
        $sourceData  = $entity->toArray();
        $keys        = [];
        foreach($targetKeys as $key) {
            $sourceColumn = isset($convert[$key]) ? $convert[$key] : $key;
            $keys[$key] = $sourceData[$sourceColumn];
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
        foreach($convert as $key => $col) {
            $keys[$col] = $keys[$key];
            unset($keys[$key]);
        }
        return $repo->find($keys);
    }
    
    public function hasJoin(EntityInterface $entity, RepositoryInterface $repo, RepositoryInterface $join)
    {
        
    }
}