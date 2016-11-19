<?php
/**
 * Created by PhpStorm.
 * User: asao
 * Date: 2016/11/19
 * Time: 11:27
 */
namespace WScore\Repository\Assembly;

use WScore\Repository\Entity\EntityInterface;

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @param EntityInterface[] $entities
     */
    public function setEntities($entities);

    /**
     * @param string $sql
     * @param array  $data
     */
    public function execute($sql, $data = []);

    /**
     * @param array $key
     */
    public function find(array $key);

    /**
     * @param array|string $key
     */
    public function findByKey($key);

    /**
     * @param string $name
     * @return CollectRelatedInterface
     */
    public function load($name);
}