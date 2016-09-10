<?php
namespace WScore\Repository\Entity;

use WScore\Repository\Repository\RepositoryInterface;

interface EntityCollectionInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * @return array
     */
    public function getKeys();

    /**
     * @param string $key
     * @return array
     */
    public function get($key);

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function fill(array $data);

    /**
     *
     */
    public function insert();

    /**
     *
     */
    public function update();

    /**
     *
     */
    public function delete();

    /**
     * @return RepositoryInterface
     */
    public function getRepository();
}