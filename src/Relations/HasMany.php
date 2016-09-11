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
     * @return QueryInterface
     */
    public function query()
    {
        $primaryKeys = $this->sourceEntity->getKeys();
        return $this->targetRepo->query()
            ->condition($primaryKeys);
    }

    /**
     * @param string $order
     * @return RelationInterface
     */
    public function orderBy($order)
    {
        throw new \BadMethodCallException('not implemented yet.');
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

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity)
    {
        $primaryKeys = $this->sourceEntity->getKeys();
        foreach($primaryKeys as $key => $val) {
            $primaryKeys[$key] = null;
        }
        $primaryKeys = HelperMethods::convertDataKeys($primaryKeys, $this->convert);
        $entity->fill($primaryKeys);

        return true;
    }
}
