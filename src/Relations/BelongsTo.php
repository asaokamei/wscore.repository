<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class BelongsTo extends AbstractRelation implements RelationInterface
{
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
        $this->convert      = $convert ?: $this->makeConversion($targetRepo);
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
     * @return QueryInterface
     */
    public function query()
    {
        $query = $this->targetRepo->query()
            ->condition($this->condition);
        
        if (!empty($this->sourceEntity)) {
            $keys = [];
            foreach($this->sourceEntity as $entity) {
                $keys[] = $this->getTargetKeys($entity);
            }
            $query->condition([$keys]);
        }
        return $query;
    }

    /**
     * @param EntityInterface $targetEntity
     * @return EntityInterface
     */
    public function relate(EntityInterface $targetEntity)
    {
        foreach($this->sourceEntity as $sourceEntity) {
            $sourceEntity->setForeignKeys($targetEntity, $this->convert);
        }

        return $targetEntity;
    }
}