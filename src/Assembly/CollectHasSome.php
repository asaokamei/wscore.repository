<?php
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

class CollectHasSome extends Collection implements CollectRelatedInterface
{
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
     * @return CollectRelatedInterface
     */
    public static function forge($repository, $relation, array $fromEntities)
    {
        $self = new self($repository, $relation);
        $self->loadRelatedEntities($fromEntities);

        return $self;
    }

    /**
     * @param EntityInterface[] $fromEntities
     */
    private function loadRelatedEntities(array $fromEntities)
    {
        if (empty($fromEntities)) {
            return;
        }
        $this->setConvert($fromEntities[0]);
        $this->findEntities($fromEntities);
    }

    /**
     * @param EntityInterface $fromEntity
     * @return Collection|EntityInterface[]
     */
    public function getRelatedEntities($fromEntity)
    {
        $keys = $this->relation->getTargetKeys($fromEntity);
        $key  = HelperMethods::flattenKey($keys);
        $found = array_key_exists($key, $this->indexed) ? $this->indexed[$key] : [];
        return $this->repository->newCollection($found, $this->relation->withEntity($fromEntity));
    }

    /**
     * @param array $entities
     */
    private function findEntities(array $entities)
    {
        $found = $this->relation->withEntity(...$entities)->collect()->toArray();
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
    }
}