<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class CollectHasSome extends Collection implements CollectRelatedInterface
{
    /**
     * @var RelationInterface
     */
    private $relation;

    /**
     * @var array
     */
    private $convert = [];

    /**
     * @var Collection[]|EntityInterface[][]
     */
    private $indexed = [];

    /**
     * @param RepositoryInterface $repository
     * @param RelationInterface   $relation
     * @param EntityInterface[]   $fromEntities
     * @return CollectHasSome
     */
    public static function forge($repository, $relation, array $fromEntities)
    {
        $self = new self($repository);
        $self->loadRelatedEntities($relation, $fromEntities);

        return $self;
    }

    /**
     * @param RelationInterface $relation
     * @param EntityInterface[] $fromEntities
     */
    private function loadRelatedEntities($relation, array $fromEntities)
    {
        $this->relation = $relation;
        if (empty($fromEntities)) {
            return;
        }
        $this->setConvert($fromEntities[0]);
        $this->findEntities($fromEntities);
    }

    /**
     * @param EntityInterface $fromEntity
     * @return EntityInterface[]
     */
    public function getRelatedEntities($fromEntity)
    {
        $keys = $this->relation->getTargetKeys($fromEntity);
        $key  = HelperMethods::flattenKey($keys);
        return array_key_exists($key, $this->indexed) ?
            $this->indexed[$key] : [];
    }

    /**
     * @param array $entities
     */
    private function findEntities(array $entities)
    {
        $keys = [];
        foreach ($entities as $entity) {
            $keys[] = $this->relation->getTargetKeys($entity);
        }
        $found = $this->relation->query()->condition([$keys])->find();
        $this->setEntities($found);
        $this->indexFound($found);
    }

    /**
     * @param EntityInterface $entity
     */
    private function setConvert($entity)
    {
        $keys          = $this->relation->getTargetKeys($entity);
        $this->convert = array_keys($keys);
    }

    /**
     * @param EntityInterface[] $found
     */
    private function indexFound(array $found)
    {
        foreach ($found as $entity) {
            $key                   = HelperMethods::flatKey($entity, $this->convert);
            $this->indexed[$key][] = $entity;
        }
        // convert to Collection.
        foreach ($this->indexed as $key => $entities) {
            $this->indexed[$key] = $this->repository->newCollection($entities);
        }
    }
}