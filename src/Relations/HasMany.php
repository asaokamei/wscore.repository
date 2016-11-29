<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class HasMany implements RelationInterface
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
     * @var EntityInterface
     */
    private $sourceEntity;

    /**
     * @var array
     */
    private $convert;

    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $targetRepo
     * @param array               $convert
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        $convert = []
    ) {
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->convert      = $convert ?: $this->makeConversion();
    }

    /**
     * @return RepositoryInterface
     */
    public function getTargetRepository()
    {
        return $this->targetRepo;
    }

    /**
     * @return array
     */
    private function makeConversion()
    {
        $convert = [];
        foreach($this->sourceRepo->getKeyColumns() as $key) {
            $convert[$key] = $key;
        }
        return $convert;
    }

    /**
     * @param EntityInterface $joinEntity
     * @return array
     */
    public function getTargetKeys(EntityInterface $joinEntity)
    {
        $keys = $joinEntity->toArray();
        $keys = HelperMethods::filterDataByKeys($keys, array_flip($this->convert));
        return HelperMethods::convertDataKeys($keys, $this->convert);
    }
    
    /**
     * @param EntityInterface $sourceEntity
     * @return static
     */
    public function withEntity(EntityInterface $sourceEntity)
    {
        $self = clone $this;
        $self->sourceEntity = $sourceEntity;

        return $self;
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $query = $this->targetRepo->query();
        if ($this->sourceEntity) {
            $primaryKeys = $this->getTargetKeys($this->sourceEntity);
            $query->condition($primaryKeys);
        }
        return $query;
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function collect($keys = [])
    {
        $found = $this->query()->select($keys)->fetchAll();
        return $this->targetRepo->newCollection($found, $this);
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
        $entity->setForeignKeys($this->sourceEntity, $this->convert);

        return $entity;
    }
}
