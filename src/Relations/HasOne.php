<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class HasOne implements RelationInterface
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
        $this->convert      = $convert;
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
        $primaryKeys = $this->getPrimaryKeys();
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
        $this->sourceEntity->relate($entity, $this->convert);

        return $entity;
    }

    /**
     * @return array
     */
    private function getPrimaryKeys()
    {
        $sourceData  = $this->sourceEntity->toArray();
        $targetKeys  = $this->targetRepo->getKeyColumns();
        $primaryKeys = HelperMethods::filterDataByKeys($sourceData, $this->convert);
        $primaryKeys = HelperMethods::filterDataByKeys($primaryKeys, $targetKeys);

        return $primaryKeys;
    }
}