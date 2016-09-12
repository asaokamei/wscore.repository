<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class JoinTo implements JoinRelationInterface
{
    /**
     * @var RepositoryInterface
     */
    private $sourceRepo;

    /**
     * @var RepositoryInterface
     */
    private $targetRepo;

    /**
     * @var JoinRepositoryInterface
     */
    private $joinRepo;

    /**
     * @var EntityInterface
     */
    private $sourceEntity;

    /**
     * @var array
     */
    private $join_on;

    /**
     * @param RepositoryInterface     $sourceRepo
     * @param RepositoryInterface     $targetRepo
     * @param JoinRepositoryInterface $joinRepo
     * @param EntityInterface         $sourceEntity
     * @param array                   $join_on
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        JoinRepositoryInterface $joinRepo,
        EntityInterface $sourceEntity,
        $join_on = []
    ) {
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->joinRepo     = $joinRepo;
        $this->sourceEntity = $sourceEntity;
        $this->join_on      = $join_on;
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $primaryKeys = $this->sourceEntity->getKeys();
        $primaryKeys = HelperMethods::convertDataKeys($primaryKeys, $this->join_on);
        $targetTable = $this->targetRepo->getTable();
        return $this->targetRepo
            ->query()
            ->join($targetTable, $this->join_on)
            ->condition($primaryKeys);
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys = [])
    {
        return $this->query()->condition($keys)->select()->fetchAll();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->query()->count();
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
        return $this->joinRepo->delete($this->sourceEntity, $entity);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->joinRepo->delete($this->sourceEntity, null);
    }

    /**
     * @return JoinEntityInterface[]
     */
    public function getJoinEntities()
    {
        return $this->joinRepo->selectFrom($this->sourceEntity);
    }
}