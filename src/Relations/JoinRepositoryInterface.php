<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

interface JoinRepositoryInterface
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
    public function create($data);

    /**
     * returns QueryInterface on join table.
     *
     * @param EntityInterface|null $entity1
     * @param EntityInterface|null $entity2
     * @return QueryInterface
     */
    public function queryJoin($entity1 = null, $entity2 = null);
    
    /**
     * @param EntityInterface $entity
     * @return QueryInterface
     */
    public function queryTarget($entity);

    /**
     * @param string $key
     * @return EntityInterface
     */
    public function findByKey($key);

    /**
     * @param EntityInterface $entity
     * @return EntityInterface[]
     */
    public function select($entity);

    /**
     * @param EntityInterface $entity1
     * @param EntityInterface $entity2
     * @return bool|EntityInterface
     */
    public function insert($entity1, $entity2);

    /**
     * @param EntityInterface      $entity1
     * @param EntityInterface|null $entity2
     * @return bool
     */
    public function delete($entity1, $entity2 = null);
}