<?php
namespace WScore\Repository\Entity;

use BadMethodCallException;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @Override
     * @var string
     */
    protected $table;

    /**
     * @var array|string[]
     */
    protected $data = [];

    /**
     * @Override
     * @var string[]
     */
    protected $primaryKeys = [];

    /**
     * sets value object class name for each column.
     * The value object is constructed as new ValueObject($value),
     * or a callable that will convert a value to an object.
     *
     * [ column-name  =>  value-object class name]
     *
     * @Override
     * @var string[]|callable[]
     */
    protected $valueObjectClasses = [];

    /**
     * a flag to check if the operation is PDO's fetchObject method.
     * set to true by using setFetchDone method inside constructor. 
     *
     * @var bool
     */
    private $isFetchDone = false;

    /**
     * a flag indicating that this entity is fetched
     * from a database. fetched if it is true, and created if false.
     *
     * @var bool
     */
    private $isFetched = false;

    /**
     * @var RepositoryInterface
     */
    protected $repo;

    /**
     * @var Collection[]
     */
    protected $relatedEntities = [];

    /**
     * @var RelationInterface[]|JoinRelationInterface[]
     */
    protected $relations = [];

    /**
     * call this method in constructor. 
     * it will protect from using __set method to 
     * overwrite entity data. 
     */
    protected function _setFetchDone()
    {
        $this->isFetchDone = true;
    }

    /**
     * @return bool
     */
    protected function _isFetchProcessDone()
    {
        return $this->isFetchDone;
    }

    /**
     * call this method to indicate that the entity is fetched from a database. 
     * sets isFetched flag to true.
     */
    protected function _setFetchedFromDb()
    {
        $this->isFetched = true;
    }
    
    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * @return string[]
     */
    public function getKeyColumns()
    {
        return $this->primaryKeys;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @throws \BadMethodCallException
     */
    public function __set($key, $value)
    {
        if ($this->_isFetchProcessDone()) {
            throw new BadMethodCallException('cannot set properties.');
        }
        $this->_setFetchedFromDb();
        $this->data[$key] = $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        // just check repository's method. may not return relation object though...
        if (method_exists($this->repo, $key)) {
            return true;
        }
        if (array_key_exists($key, $this->data)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $id
     * @throws \BadMethodCallException
     */
    public function setPrimaryKeyOnCreatedEntity($id)
    {
        /**
         * not sure if the following assertion is useful.
         * commented out for now.
         *//*
        if ($this->isFetched) {
            throw new BadMethodCallException('cannot set primary key on a fetched entity.');
        } */
        if ($id !== true && $id) {
            $key = $this->getIdName();
            $this->data[$key] = $id;
        }
        $this->_setFetchedFromDb();
    }

    /**
     * @param string $key
     * @return null|string|Collection
     */
    public function get($key)
    {
        if ($collection = $this->_getRelatedEntities($key)) {
            return $collection;
        }
        $value = array_key_exists($key, $this->data) ? $this->data[$key] : null;
        if (isset($this->valueObjectClasses[$key])) {
            return HelperMethods::convertToObject($value, $this->valueObjectClasses[$key]);
        }
        return $value;
    }

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function fill(array $data)
    {
        $this->data = array_merge(
            $this->data,
            $data
        );

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return HelperMethods::filterDataByKeys($this->data, $this->getKeyColumns());
    }

    /**
     * @return string
     * @throws \BadMethodCallException
     */
    public function getIdValue()
    {
        return $this->get($this->getIdName());
    }

    /**
     * @return string
     * @throws \BadMethodCallException
     */
    public function getIdName()
    {
        $keys = $this->getKeyColumns();
        if (empty($keys)) {
            throw new \BadMethodCallException('keys not set.');
        }
        if (count($keys) !== 1) {
            throw new \BadMethodCallException('multiple keys set.');
        }
        return $keys[0];
    }

    /**
     * @return bool
     */
    public function isFetched()
    {
        return $this->isFetched;
    }

    /**
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function setForeignKeys(EntityInterface $entity, array $convert = [])
    {
        $keys = $entity->getKeys();
        $keys = HelperMethods::convertDataKeys($keys, $convert);
        $this->data = array_merge($this->data, $keys);
    }

    /**6
     * @return bool
     */
    public function save()
    {
        $this->repo->save($this);
        return true;
    }

    /**
     * @param string $name
     * @param array  $args
     * @return JoinRelationInterface|RelationInterface|mixed
     * @throws \BadMethodCallException
     */
    public function __call($name, $args)
    {
        if ($relation = $this->_getRelationObject($name)) {
            return $relation;
        }

        throw new BadMethodCallException('no such methods: '. $name);
    }

    /**
     * @param string $name
     * @return null|JoinRelationInterface|RelationInterface
     */
    private function _getRelationObject($name)
    {
        if (isset($this->relations[$name])) {
            return $this->relations[$name]->withEntity($this);
        }
        if (!method_exists($this->repo, $name)) {
            return null;
        }
        $relation = $this->repo->$name();
        if ($relation instanceof RelationInterface) {
            $this->relations[$name] = $relation;
            return $relation->withEntity($this);
        }
        return null;
    }

    /**
     * @param string $name
     * @return JoinRelationInterface|RelationInterface
     * @throws \InvalidArgumentException
     */
    public function getRelationObject($name)
    {
        if ($relation = $this->_getRelationObject($name)) {
            return $relation->withEntity($this);
        }
        throw new \InvalidArgumentException('no such method: ' . (string) $name);
    }

    /**
     * @param string $name
     * @return null|Collection
     */
    protected function _getRelatedEntities($name)
    {
        if (array_key_exists($name, $this->relatedEntities)) {
            return $this->relatedEntities[$name];
        }
        if ($relation = $this->_getRelationObject($name)) {
            return $relation->collect();
        }
        return null;
    }

    /**
     * @param string $name
     * @return Collection
     * @throws \InvalidArgumentException
     */
    public function getRelatedEntities($name)
    {
        if ($collection = $this->_getRelatedEntities($name)) {
            return $collection;
        }
        throw new \InvalidArgumentException('no such relation: ' . (string) $name);
    }

    /**
     * @param string $name
     * @return null|string|Collection
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param Collection $entities
     */
    public function setRelatedEntities($name, $entities)
    {
        $this->relatedEntities[$name] = $entities;
    }
}