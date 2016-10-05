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
     * @param EntityInterface|null $fromEntity
     * @param EntityInterface|null $toEntity
     * @return QueryInterface
     */
    public function queryJoin($fromEntity = null, $toEntity = null);
    
    /**
     * returns QueryInterface on targeted table, opposite of $entity's table.
     *
     * @param EntityInterface $fromEntity
     * @return QueryInterface
     */
    public function queryTarget($fromEntity);

    /**
     * @param string $key
     * @return EntityInterface
     */
    public function findByKey($key);

    /**
     * @param EntityInterface $fromEntity
     * @return EntityInterface[]
     */
    public function select($fromEntity);

    /**
     * @param EntityInterface $fromEntity
     * @param EntityInterface $toEntity
     * @return bool|EntityInterface
     */
    public function insert($fromEntity, $toEntity);

    /**
     * @param EntityInterface      $fromEntity
     * @param EntityInterface|null $toEntity
     * @return bool
     */
    public function delete($fromEntity, $toEntity = null);
}