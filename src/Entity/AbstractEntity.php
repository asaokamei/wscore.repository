<?php
namespace WScore\Repository\Entity;

use BadMethodCallException;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\Repository\RepositoryInterface;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @Override
     * @var string
     */
    private $table;

    /**
     * @var array|string[]
     */
    private $data = [];

    /**
     * @var array|string[]
     */
    private $_original_data = [];

    /**
     * @Override
     * @var string[]
     */
    private $primaryKeys = [];

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
     * AbstractEntity constructor.
     *
     * @param string $table
     * @param array  $primaryKeys
     */
    public function __construct($table, array $primaryKeys)
    {
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys;
        $this->setFetchDone();
    }

    /**
     * call this method in constructor. 
     * it will protect from using __set method to 
     * overwrite entity data. 
     */
    protected function setFetchDone()
    {
        $this->isFetchDone = true;
    }

    /**
     * @return bool
     */
    protected function isFetchProcessDone()
    {
        return $this->isFetchDone;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    protected function _setOriginalData($key, $value)
    {
        $this->_original_data[$key] = $value;
    }

    /**
     * @param string $key
     * @return array|string
     */
    protected function _getOriginalData($key = null)
    {
        if (is_null($key)) {
            return $this->_original_data;
        }
        return array_key_exists($key, $this->_original_data) ? $this->_original_data[$key] : null;
    }

    /**
     * call this method to indicate that the entity is fetched from a database. 
     * sets isFetched flag to true.
     */
    protected function setFetchedFromDb()
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
     * @return array
     */
    public function getKeyColumns()
    {
        return $this->primaryKeys;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        if ($this->isFetchProcessDone()) {
            throw new BadMethodCallException('cannot set properties.');
        }
        $this->setFetchedFromDb();
        $this->data[$key] = $value;
        $this->_setOriginalData($key, $value);
    }

    /**
     * @param string $id
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
            $this->_setOriginalData($key, $id);
        }
        $this->setFetchedFromDb();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
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
    public function getUpdatedData()
    {
        $array = [];
        // find only the key/value that are modified.
        foreach($this->data as $key => $value) {
            if ($value === $this->_getOriginalData($key)) {
                continue; // value has not changed. so ignore it.
            }
            $array[$key] = $value;
        }
        return $array;
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
     */
    public function getIdValue()
    {
        return $this->get($this->getIdName());
    }

    /**
     * @return string
     */
    public function getIdName()
    {
        $keys = $this->getKeyColumns();
        if (!$keys) {
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
    public function relate(EntityInterface $entity, $convert = [])
    {
        $keys = $entity->getKeys();
        $keys = HelperMethods::convertDataKeys($keys, $convert);
        $this->data = array_merge($this->data, $keys);
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->isFetched()) {
            return $this->repo->update($this);
        }
        $this->repo->insert($this);
        return true;
    }

}