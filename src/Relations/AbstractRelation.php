<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractRelation implements RelationInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $sourceRepo;

    /**
     * @var RepositoryInterface
     */
    protected $targetRepo;

    /**
     * @var EntityInterface[]
     */
    protected $sourceEntities;

    /**
     * @var array
     */
    protected $convert;

    /**
     * @var array
     */
    protected $condition = [];

    /**
     * @return RepositoryInterface
     */
    public function getTargetRepository()
    {
        return $this->targetRepo;
    }

    /**
     * @param RepositoryInterface $repository
     * @return array
     */
    protected function makeConversion(RepositoryInterface $repository)
    {
        $convert = [];
        foreach($repository->getKeyColumns() as $k) {
            $convert[$k] = $k;
        }
        return $convert;
    }

    /**
     * @param EntityInterface[] ...$sourceEntity
     * @return RelationInterface|JoinRelationInterface
     */
    public function withEntity(...$sourceEntity)
    {
        $self = clone $this;
        $self->sourceEntities = $sourceEntity;

        return $self;
    }

    /**
     * @param array $key
     * @return $this
     */
    public function setCondition(array $key)
    {
        $this->condition = $key;
        return $this;
    }
    
    /**
     * @return int
     */
    public function count()
    {
        return $this->query()->count();
    }

    /**
     * @param array $keys
     * @return Collection|EntityInterface[]
     */
    public function collect($keys = [])
    {
        $query = $this->query();
        $found = $query ? $query->select($keys)->fetchAll() : [];
        return $this->targetRepo->newCollection($found, $this);
    }

    /**
     * extract keys for querying entity.
     * 
     * @param EntityInterface $entity
     * @param $conversion
     * @return array
     */
    protected function extractKeys($entity, $conversion)
    {
        $data = $entity->toArray();
        $keys = HelperMethods::filterDataByKeys($data, array_flip($conversion));
        $keys = HelperMethods::convertDataKeys($keys, $conversion);

        return $keys;
    }

    /**
     * @return QueryInterface
     */
    abstract public function query();
}