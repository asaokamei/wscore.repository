<?php
//namespace WScore\Repository;

interface RelationInterface
{
    /**
     * @param string $order
     * @return QueryInterface
     */
    public function orderBy($order);

    /**
     * @param array $keys
     * @return EntityInterface
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
    public function insert(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity);
}