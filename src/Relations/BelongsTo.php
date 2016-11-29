<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class BelongsTo implements RelationInterface
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
        foreach($this->targetRepo->getKeyColumns() as $k) {
            $convert[$k] = $k;
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
        $keys = HelperMethods::convertDataKeys($keys, $this->convert);

        return $keys;
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
        $primaryKeys = $this->getTargetKeys($this->sourceEntity);
        if ($this->sourceEntity && empty($primaryKeys)) {
            throw new \InvalidArgumentException('cannot convert primary key.');
        }
        return $this->targetRepo->query()
                                ->condition($primaryKeys);
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
        $this->sourceEntity->setForeignKeys($entity, $this->convert);

        return $entity;
    }
}