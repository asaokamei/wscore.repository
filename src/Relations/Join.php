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
        RepositoryInterface $toRepo,
        RepositoryInterface $joinRepo,
        array $from_convert = [],
        array $to_convert = []
    ) {
        $this->joinRepo     = $joinRepo;
        $this->fromRepo     = $fromRepo;
        $this->toRepo       = $toRepo;
        $this->from_convert = $from_convert ?: $this->makeConvertKey($fromRepo->getKeyColumns());
        $this->to_convert   = $to_convert ?: $this->makeConvertKey($toRepo->getKeyColumns());
    }

    /**
     * @return RepositoryInterface
     */
    public function getTargetRepository()
    {
        return $this->toRepo;
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
     * get keys for join record from to-entity.
     * 
     * @param EntityInterface $targetEntity
     * @return array
     */
    private function convertToKeys(EntityInterface $targetEntity)
    {
        return HelperMethods::convertDataKeys($targetEntity->getKeys(), array_flip($this->to_convert));
    }

    /**
     * @param EntityInterface $sourceEntity
     * @return array
     */
    public function getJoinKeys(EntityInterface $sourceEntity)
    {
        $data = $sourceEntity->toArray();
        $keys = HelperMethods::filterDataByKeys($data, array_flip($this->from_convert));
        $keys = HelperMethods::convertDataKeys($keys, $this->from_convert);
        return $keys;
    }

    /**
     * @param EntityInterface $joinEntity
     * @return array
     */
    public function getTargetKeys(EntityInterface $joinEntity)
    {
        $data = $joinEntity->toArray();
        $keys = HelperMethods::filterDataByKeys($data, array_flip($this->to_convert));
        $keys = HelperMethods::convertDataKeys($keys, $this->to_convert);
        return $keys;
    }
    
    /**
     * @param EntityInterface $sourceEntity
     * @return static
     */
    public function withEntity(EntityInterface $sourceEntity)
    {
        $this->sourceEntity = $sourceEntity;

        return $this;
    }

    /**
     * @param null|EntityInterface $targetEntity
     * @return QueryInterface
     */
    public function queryJoin($targetEntity = null)
    {
        $keys = [];
        if ($this->sourceEntity) {
            $keys = $this->getJoinKeys($this->sourceEntity);
        }
        if ($targetEntity) {
            $keys = array_merge(
                $keys,
                $this->convertToKeys($targetEntity)
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
     * @param EntityInterface $targetEntity
     * @return bool
     */
    public function delete(EntityInterface $targetEntity)
    {
        if (!$this->sourceEntity) {
            throw new \BadMethodCallException('must have source entity to delete.');
        }
        $keys = $this->convertToKeys($targetEntity);
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
            $this->getJoinKeys($this->sourceEntity),
            $this->convertToKeys($entity));
        $join = $this->joinRepo->create($keys);
        $this->joinRepo->insert($join);
        
        return $join;
    }
}