<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\QueryInterface;

interface RelationInterface
{
    /**
     * @return QueryInterface
     */
    public function query();

    /**
     * @param string $order
     * @return RelationInterface
     */
    public function orderBy($order);

    /**
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find($keys = []);

    /**
     * @return int
     */
    public function count();

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function relate(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity);
}