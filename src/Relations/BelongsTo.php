<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class BelongsTo extends AbstractRelation
{
    /**
     * @param RepositoryInterface $sourceRepo
     * @param RepositoryInterface $targetRepo
     * @param array               $convert
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        array $convert = []
    ) {
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->convert      = $convert ?: $this->makeConversion($targetRepo);
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    public function getTargetKeys(EntityInterface $entity)
    {
        return $this->extractKeys($entity, $this->convert);
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $query = $this->targetRepo->query()
            ->condition($this->condition);
        
        if (!empty($this->sourceEntities)) {
            $keys = [];
            foreach($this->sourceEntities as $entity) {
                $keys[] = $this->getTargetKeys($entity);
            }
            $query->condition([$keys]);
        }
        return $query;
    }

    /**
     * @param EntityInterface $targetEntity
     */
    public function relate(EntityInterface $targetEntity)
    {
        foreach($this->sourceEntities as $sourceEntity) {
            $sourceEntity->setForeignKeys($targetEntity, $this->convert);
        }
    }
}