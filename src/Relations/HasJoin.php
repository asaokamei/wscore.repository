<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

class HasJoin implements JoinRelationInterface
{
    /**
     * @var JoinRepositoryInterface
     */
    private $joinRepo;

    /**
     * @var EntityInterface
     */
    private $sourceEntity;

    /**
     * @param JoinRepositoryInterface $joinRepo
     * @param EntityInterface         $sourceEntity
     */
    public function __construct(
        JoinRepositoryInterface $joinRepo,
        EntityInterface $sourceEntity
    ) {
        $this->joinRepo     = $joinRepo;
        $this->sourceEntity = $sourceEntity;
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        return $this->joinRepo
            ->queryTarget($this->sourceEntity);
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys = [])
    {
        return $this->joinRepo
            ->queryTarget($this->sourceEntity)
            ->select($keys)
            ->fetchAll();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->joinRepo
            ->queryTarget($this->sourceEntity)
            ->count();
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function relate(EntityInterface $entity)
    {
        $this->joinRepo->insert($this->sourceEntity, $entity);

        return $entity;
    }

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity)
    {
        return $this->joinRepo
            ->delete($this->sourceEntity, $entity);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->joinRepo
            ->delete($this->sourceEntity, null);
    }

    /**
     * @param null|EntityInterface $entity
     * @return QueryInterface
     */
    public function queryJoin($entity = null)
    {
        return $this->joinRepo->queryJoin($this->sourceEntity, $entity);
    }
}