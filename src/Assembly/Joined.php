<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class Joined extends Entities
{
    /**
     * @var JoinRelationInterface
     */
    private $relation;

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
     * @param RepositoryInterface   $repository
     * @param JoinRelationInterface $relation
     * @param EntityInterface[]     $fromEntities
     * @return self
     */
    public static function forge($repository, $relation, array $fromEntities)
    {
        $self = new self($repository);
        $self->load($relation, $fromEntities);

        return $self;
    }

    /**
     * @param JoinRelationInterface $relation
     * @param EntityInterface[]     $fromEntities
     */
    private function load($relation, array $fromEntities)
    {
        $this->relation = $relation;
        if (empty($fromEntities)) {
            return;
        }
        $this->setConvertJoin($fromEntities[0]);
        $this->findJoinEntities($fromEntities);
    }

    /**
     * @param EntityInterface $fromEntity
     * @return EntityInterface[]
     */
    public function find($fromEntity)
    {
        $key = HelperMethods::flatKey($fromEntity, $this->convertJoin);
        if (!array_key_exists($key, $this->indexedJoin)) {
            return [];
        }
        $joinKeys = $this->indexedJoin[$key];
        $found    = [];
        foreach ($joinKeys as $join) {
            $key = HelperMethods::flatKey($join, $this->convertTo);
            if (array_key_exists($key, $this->indexedTo)) {
                $found = array_merge($found, $this->indexedTo[$key]);
            }
        }
        return $found;
    }

    /**
     * @param EntityInterface $entity
     */
    private function setConvertJoin($entity)
    {
        $keys              = $this->relation->convertFromKeys($entity);
        $this->convertJoin = array_keys($keys);
    }

    /**
     * @param EntityInterface[] $fromEntities
     */
    private function findJoinEntities($fromEntities)
    {
        $keys = [];
        foreach ($fromEntities as $entity) {
            $keys[] = $this->relation->convertFromKeys($entity);
        }
        $found = $this->relation->queryJoin()->condition([$keys])->find();

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
        $this->setConvertTo($joinEntities[0]);

        $keys = [];
        foreach ($joinEntities as $entity) {
            $keys[] = $this->relation->getTargetKeys($entity);
        }
        $found = $this->relation->query()->condition($keys)->find();
        $this->entities($found);
        /** @var EntityInterface[] $found */
        foreach ($found as $toEntity) {
            $key                     = HelperMethods::flatKey($toEntity, $this->convertTo);
            $this->indexedTo[$key][] = $toEntity;
        }
    }

    /**
     * @param EntityInterface $entity
     */
    private function setConvertTo($entity)
    {
        $keys            = $this->relation->getTargetKeys($entity);
        $this->convertTo = array_keys($keys);

    }
}