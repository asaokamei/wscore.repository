<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractRelation
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
     * @var EntityInterface
     */
    protected $sourceEntity;

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
     * @return AbstractRelation
     */
    public function withEntity(EntityInterface ...$sourceEntity)
    {
        $self = clone $this;
        $self->sourceEntity = $sourceEntity;

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
        $found = $this->query()->select($keys)->fetchAll();
        return $this->targetRepo->newCollection($found, $this);
    }

    /**
     * @return QueryInterface
     */
    abstract public function query();
}