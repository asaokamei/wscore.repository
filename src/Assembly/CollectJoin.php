<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class CollectJoin extends Collection
{
    /**
     * @var array
     */
    private $convertJoin = [];

    /**
     * @var array
     */
    private $convertTo = [];

    /**
     * @var EntityInterface[][]
     */
    private $indexedJoin = [];

    /**
     * @var EntityInterface[][]
     */
    private $indexedTo = [];

    /**
     * @param RepositoryInterface $repository
     * @param JoinRelationInterface $relation
     */
    public function __construct($repository, $relation)
    {
        parent::__construct($repository, $relation);
    }

    /**
     * @param RepositoryInterface   $repository
     * @param JoinRelationInterface $relation
     * @param EntityInterface[]     $fromEntities
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public static function forge($repository, $relation, array $fromEntities)
    {
        if (!$relation instanceof JoinRelationInterface) {
            throw new \InvalidArgumentException('$relation not JoinRelationInterface');
        }
        $self = new self($repository, $relation);
        $self->loadRelatedEntities($fromEntities);

        return $self;
    }

    /**
     * @param EntityInterface[]     $fromEntities
     */
    private function loadRelatedEntities(array $fromEntities)
    {
        if (empty($fromEntities)) {
            return;
        }
        $this->setConvertJoin($fromEntities[0]);
        $this->findJoinEntities($fromEntities);
    }

    /**
     * @param EntityInterface $fromEntity
     * @return Collection
     */
    public function getRelatedEntities($fromEntity)
    {
        $key = $this->relation->join()->getJoinKeys($fromEntity);
        $key = HelperMethods::flattenKey($key);
        if (!array_key_exists($key, $this->indexedJoin)) {
            return $this->repository->newCollection([], $this->relation);
        }
        $joinKeys = $this->indexedJoin[$key];
        $found    = [];
        foreach ($joinKeys as $join) {
            $key = $this->relation->getTargetKeys($join);
            $key = HelperMethods::flattenKey($key);
            if (array_key_exists($key, $this->indexedTo)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $found = array_merge($found, $this->indexedTo[$key]);
            }
        }
        return $this->repository->newCollection($found, $this->relation->withEntity($fromEntity));
    }

    /**
     * @param EntityInterface $entity
     */
    private function setConvertJoin($entity)
    {
        $keys              = $this->relation->join()->getJoinKeys($entity);
        $this->convertJoin = array_keys($keys);
    }

    /**
     * @param EntityInterface[] $fromEntities
     */
    private function findJoinEntities($fromEntities)
    {
        $found = $this->relation
            ->withEntity(...$fromEntities)
            ->join()->queryJoin()->find();

        /** @var EntityInterface[] $found */
        foreach ($found as $join) {        
            $key                       = HelperMethods::flatKey($join, $this->convertJoin);
            $this->indexedJoin[$key][] = $join;
        }

        $this->loadTo($found);
    }

    /**
     * @param EntityInterface[] $joinEntities
     */
    private function loadTo($joinEntities)
    {
        if (empty($joinEntities)) {
            return;
        }
        $found = $this->relation->join()->queryTarget(...$joinEntities)->find();
        $this->setEntities($found);
        /** @var EntityInterface[] $found */
        foreach ($found as $toEntity) {
            $key                     = HelperMethods::flatKey($toEntity, $this->convertTo);
            $this->indexedTo[$key][] = $toEntity;
        }
    }
}