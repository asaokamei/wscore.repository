<?php
namespace WScore\Repository\Relations;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;

interface RelationInterface
{
    /**
     * @param EntityInterface $entity
     * @return static
     */
    public function withEntity(EntityInterface $entity);
    
    /**
     * @return QueryInterface
     */
    public function query();

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
}