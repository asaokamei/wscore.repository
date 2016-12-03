<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class HasMany extends AbstractRelation implements RelationInterface
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
        $this->convert      = $convert ?: $this->makeConversion($sourceRepo);
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
     * @return QueryInterface
     */
    public function query()
    {
        $query = $this->targetRepo->query();
        $query->condition($this->condition);
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
        foreach($this->sourceEntities as $entity) {
            $targetEntity->setForeignKeys($entity, $this->convert);
        }
    }
}
