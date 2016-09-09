<?php
namespace WScore\Repository\Relation;

use WScore\Repository\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\JoinEntityInterface;
use WScore\Repository\JoinRelationInterface;
use WScore\Repository\JoinRepositoryInterface;
use WScore\Repository\QueryInterface;
use WScore\Repository\RepositoryInterface;

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
    private $convert;

    /**
     * @param RepositoryInterface     $sourceRepo
     * @param RepositoryInterface     $targetRepo
     * @param JoinRepositoryInterface $joinRepo
     * @param EntityInterface         $sourceEntity
     * @param array                   $convert
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        JoinRepositoryInterface $joinRepo,
        EntityInterface $sourceEntity,
        $convert = []
    ) {
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->joinRepo     = $joinRepo;
        $this->sourceEntity = $sourceEntity;
        $this->convert      = $convert;
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $primaryKeys = $this->sourceEntity->getKeys();
        $primaryKeys = HelperMethods::convertDataKeys($primaryKeys, $this->convert);
        $targetTable = $this->targetRepo->getTable();
        return $this->targetRepo
            ->query()
            ->join($targetTable, $this->convert)
            ->condition($primaryKeys);
    }

    /**
     * @param string $order
     * @return JoinRelationInterface
     */
    public function orderBy($order)
    {
        throw new \BadMethodCallException('not implemented yet.');
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