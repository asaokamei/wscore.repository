<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class Join extends AbstractRelation implements JoinRelationInterface
{
    /**
     * @var RepositoryInterface
     */
    private $joinRepo;

    /**
     * @var array
     */
    private $from_convert = [];

    /**
     * @var array
     */
    private $to_convert = [];

    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $joinRepo
     * @param RepositoryInterface $targetRepo
     * @param array               $from_convert
     * @param array               $to_convert
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        RepositoryInterface $joinRepo,
        array $from_convert = [],
        array $to_convert = []
    ) {
        $this->joinRepo     = $joinRepo;
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->from_convert = $from_convert ?: $this->makeConvertKey($sourceRepo->getKeyColumns());
        $this->to_convert   = $to_convert ?: $this->makeConvertKey($targetRepo->getKeyColumns());
    }

    private function makeConvertKey(array $keys)
    {
        $convert = [];
        foreach($keys as $k) {
            $convert[$k] = $k;
        }
        return $convert;
    }

    /**
     * get keys for join record from target-entity.
     * 
     * @param EntityInterface $targetEntity
     * @return array
     */
    private function getJoinKeyFromTargetEntity(EntityInterface $targetEntity)
    {
        return HelperMethods::convertDataKeys($targetEntity->getKeys(), array_flip($this->to_convert));
    }

    /**
     * @param EntityInterface $sourceEntity
     * @return array
     */
    public function getJoinKeys(EntityInterface $sourceEntity)
    {
        return $this->extractKeys($sourceEntity, $this->from_convert);
    }

    /**
     * @param EntityInterface $joinEntity
     * @return array
     */
    public function getTargetKeys(EntityInterface $joinEntity)
    {
        return $this->extractKeys($joinEntity, $this->to_convert);
    }
    
    /**
     * @return QueryInterface
     */
    public function queryJoin()
    {
        $keys = $this->getSourceKeys();
        return $this->joinRepo
            ->query()
            ->condition($keys);
    }

    /**
     * @return array
     */
    private function getSourceKeys()
    {
        $keys = [];
        if (empty($this->sourceEntities)) {
            return $keys;
        }
        foreach($this->sourceEntities as $entity) {
            $keys[] = $this->getJoinKeys($entity);
        }
        return [$keys];
    }

    /**
     * @return bool
     * @throws \BadMethodCallException
     */
    public function clear()
    {
        if (empty($this->sourceEntities)) {
            throw new \BadMethodCallException('must have source entity to clear.');
        }
        return $this->queryJoin()
            ->delete([]);
    }

    /**
     * @param EntityInterface $targetEntity
     * @return bool
     * @throws \BadMethodCallException
     */
    public function delete(EntityInterface $targetEntity)
    {
        if (empty($this->sourceEntities)) {
            throw new \BadMethodCallException('must have source entity to delete.');
        }
        $keys = $this->getJoinKeyFromTargetEntity($targetEntity);
        return $this->queryJoin()
            ->delete($keys);
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $joins = $this->queryJoin()->find();
        return $this->queryTarget(...$joins);
    }

    /**
     * @param EntityInterface[] ...$joinEntities
     * @return QueryInterface|null
     */    
    public function queryTarget(...$joinEntities)
    {
        if (empty($joinEntities)) {
            return null;
        }
        $keys  = [];
        foreach($joinEntities as $j) {
            $keys[] = $this->getTargetKeys($j);
        }
        return $this->targetRepo
            ->query()
            ->condition($this->condition)
            ->condition($keys);
    }

    /**
     * @param EntityInterface $targetEntity
     * @throws \BadMethodCallException
     */
    public function relate(EntityInterface $targetEntity)
    {
        if (empty($this->sourceEntities)) {
            throw new \BadMethodCallException('must have source entity to relate.');
        }
        $targetKeys = $this->getJoinKeyFromTargetEntity($targetEntity);
        foreach($this->sourceEntities as $sourceEntity) {
            $keys = array_merge(
                $this->getJoinKeys($sourceEntity),
                $targetKeys);
            $join = $this->joinRepo->create($keys);
            $this->joinRepo->insert($join);
        }
    }
}