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
     * @param EntityInterface $entity
     * @return array
     */
    public function getTargetKeys(EntityInterface $entity)
    {
        $keys = $entity->toArray();
        $keys = HelperMethods::filterDataByKeys($keys, array_flip($this->convert));
        return HelperMethods::convertDataKeys($keys, $this->convert);
    }
    
    /**
     * @param EntityInterface $entity
     * @return static
     */
    public function withEntity(EntityInterface $entity)
    {
        $self = clone $this;
        $self->sourceEntity = $entity;

        return $self;
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $primaryKeys = $this->getTargetKeys($this->sourceEntity);
        return $this->targetRepo->query()
            ->condition($primaryKeys);
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys = [])
    {
        return $this->query()->select($keys)->fetchAll();
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
        $entity->relate($this->sourceEntity, $this->convert);

        return $entity;
    }
}
