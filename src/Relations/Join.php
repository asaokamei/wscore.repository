<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repository\RepositoryInterface;

class Join implements JoinRelationInterface
{
    /**
     * @var RepositoryInterface
     */
    private $fromRepo;

    /**
     * @var RepositoryInterface
     */
    private $joinRepo;

    /**
     * @var RepositoryInterface
     */
    private $toRepo;

    /**
     * @var EntityInterface
     */
    private $sourceEntity;

    /**
     * @var array
     */
    private $from_convert = [];

    /**
     * @var array
     */
    private $to_convert = [];

    /**
     * @param RepositoryInterface $fromRepo
     * @param RepositoryInterface $joinRepo
     * @param RepositoryInterface $toRepo
     * @param array               $from_convert
     * @param array               $to_convert
     */
    public function __construct(
        RepositoryInterface $fromRepo,
        RepositoryInterface $joinRepo,
        RepositoryInterface $toRepo,
        array $from_convert = [],
        array $to_convert = []
    ) {
        $this->joinRepo     = $joinRepo;
        $this->fromRepo     = $fromRepo;
        $this->toRepo       = $toRepo;
        $this->from_convert = $from_convert ?: $this->makeConvertKey($fromRepo->getKeyColumns());
        $this->to_convert   = $to_convert ?: $this->makeConvertKey($toRepo->getKeyColumns());
    }
    
    private function makeConvertKey($keys)
    {
        $convert = [];
        foreach($keys as $k) {
            $convert[$k] = $k;
        }
        return $convert;
    }

    /**
     * @param EntityInterface $fromEntity
     * @return array
     */
    public function convertFromKeys(EntityInterface $fromEntity)
    {
        return HelperMethods::convertDataKeys($fromEntity->getKeys(), $this->from_convert);
    }

    /**
     * @param EntityInterface $toEntity
     * @return array
     */
    public function convertToKeys(EntityInterface $toEntity)
    {
        return HelperMethods::convertDataKeys($toEntity->getKeys(), $this->to_convert);
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    public function getTargetKeys(EntityInterface $entity)
    {
        $data = $entity->toArray();
        $keys = HelperMethods::filterDataByKeys($data, $this->to_convert);
        $keys = HelperMethods::convertDataKeys($keys, $this->to_convert);
        return $keys;
    }
    
    /**
     * @param EntityInterface $entity
     * @return static
     */
    public function withEntity(EntityInterface $entity)
    {
        $this->sourceEntity = $entity;

        return $this;
    }

    /**
     * @param null|EntityInterface $entity
     * @return QueryInterface
     */
    public function queryJoin($entity = null)
    {
        $keys = [];
        if ($this->sourceEntity) {
            $keys = $this->convertFromKeys($this->sourceEntity);
        }
        if ($entity) {
            $keys = array_merge(
                $keys,
                $this->convertToKeys($entity)
            );
        }
        return $this->joinRepo
            ->query()
            ->condition($keys);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        if (!$this->sourceEntity) {
            throw new \BadMethodCallException('must have source entity to clear.');
        }
        return $this->queryJoin()
            ->delete([]);
    }

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity)
    {
        if (!$this->sourceEntity) {
            throw new \BadMethodCallException('must have source entity to delete.');
        }
        $keys = $this->convertFromKeys($entity);
        return $this->queryJoin()
            ->delete($keys);
    }

    /**
     * @return QueryInterface
     */
    public function query()
    {
        $joins = $this->queryJoin()->find();
        $keys  = [];
        foreach($joins as $j) {
            $keys[] = $this->getTargetKeys($j);
        }
        if (empty($joins)) {
            $keys = ['false'];
        }
        return $this->toRepo
            ->query()
            ->condition([$keys]);
    }

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys = [])
    {
        return $this->query()
            ->find($keys);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->query()
            ->count();
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function relate(EntityInterface $entity)
    {
        if (!$this->sourceEntity) {
            throw new \BadMethodCallException('must have source entity to relate.');
        }
        $keys = array_merge(
            $this->convertFromKeys($this->sourceEntity),
            $this->convertToKeys($entity));
        $join = $this->joinRepo->create($keys);
        $this->joinRepo->insert($join);
        
        return $join;
    }
}