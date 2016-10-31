<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

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
}