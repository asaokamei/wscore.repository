<?php
//namespace WScore\Repository;

interface RelationInterface
{
    /**
     * @param string $order
     * @return RelationInterface
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
    public function save(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity);
}