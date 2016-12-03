<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;

interface RepositoryInterface
{
    /**
     * @return string
     */
    public function getTable();

    /**
     * @return string|EntityInterface
     */
    public function getEntityClass();

    /**
     * @return string[]
     */
    public function getKeyColumns();

    /**
     * @return string[]
     */
    public function getColumnList();

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function create(array $data);

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function createAsFetched(array $data);

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find(array $keys);

    /**
     * @param array|string $keys
     * @return EntityInterface|null
     */
    public function findByKey($keys);

    /**
     * returns a brand new Collection for this repository.
     *
     * @param EntityInterface[] $entities
     * @param null|RelationInterface|JoinRelationInterface $relation
     * @return Collection
     */
    public function newCollection($entities = [], $relation = null);

    /**
     * @param array $keys
     * @return Collection
     */
    public function collectFor(array $keys);

    /**
     * @param array|string $keys
     * @return Collection
     */
    public function collectByKey($keys);

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function save(EntityInterface $entity);
    
    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function insert(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function update(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity);

    /**
     * @return QueryInterface
     */
    public function query();
    
    /**
     * @Override
     * @param \PDOStatement $stmt
     */
    public function applyFetchMode(\PDOStatement $stmt);
}