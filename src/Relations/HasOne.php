<?php
//namespace WScore\Repository\Relation;

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
     * @param EntityInterface     $sourceEntity
     * @param array               $convert
     */
    public function __construct(
        RepositoryInterface $sourceRepo,
        RepositoryInterface $targetRepo,
        EntityInterface $sourceEntity,
        $convert = []
    ) {
        $this->sourceRepo   = $sourceRepo;
        $this->targetRepo   = $targetRepo;
        $this->sourceEntity = $sourceEntity;
        $this->convert      = $convert;
    }

    /**
     * @param string $order
     * @return RelationInterface
     */
    public function orderBy($order)
    {
        return $this;
    }

    /**
     * @param array $keys
     * @return EntityInterface
     */
    public function find($keys = [])
    {
        $primaryKeys = $this->getPrimaryKeys();
        $primaryKeys = array_merge($primaryKeys, $keys);
        $found       = $this->targetRepo->find($primaryKeys);
        if ($found) {
            return $found[0];
        }

        return null;
    }

    /**
     * @return int
     */
    public function count()
    {
        $primaryKeys = $this->getPrimaryKeys();

        return $this->targetRepo->getDao()->condition($primaryKeys)->count();
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function save(EntityInterface $entity)
    {
        $this->sourceEntity->relate($entity, $this->convert);
        $this->sourceRepo->save($this->sourceEntity);

        return $entity;
    }

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity)
    {
        $keys = $entity->getKeys();
        $keys = HelperMethods::convertDataKeys($keys, $this->convert);
        $this->sourceEntity->fill($keys);

        return true;
    }

    /**
     * @return array
     */
    private function getPrimaryKeys()
    {
        $targetKeys  = $this->targetRepo->getKeyColumns();
        $sourceData  = $this->sourceEntity->toArray();
        $primaryKeys = HelperMethods::filterDataByKeys($sourceData, $targetKeys);
        $primaryKeys = HelperMethods::convertDataKeys($primaryKeys, $this->convert);

        return $primaryKeys;
    }
}