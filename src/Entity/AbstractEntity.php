<?php
namespace WScore\Repository\Entity;

use BadMethodCallException;
use WScore\Repository\Helpers\HelperMethods;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $_original_data = [];

    /**
     * @var string[]
     */
    protected $primaryKeys = [];

    /**
     * @var string[]
     */
    protected $columnList = [];
    
    /**
     * sets value object class name for each column.
     * The value object is constructed as new ValueObject($value),
     * or a callable that will convert a value to an object.
     *
     * [ column-name  =>  value-object class name]
     *
     * @var string[]|callable{}
     */
    protected $valueObjectClasses = [];

    /**
     * this flag turns true before constructor is called,
     * i.e. it is false during PDO's fetchObject method.
     *
     * @var bool
     */
    protected $isFetchDone = false;

    /**
     * a flag indicating that this entity is fetched
     * from a database if it is true.
     *
     * @var bool
     */
    protected $isFetched = false;

    /**
     * @return array
     */
    public function getKeyColumns()
    {
        return $this->primaryKeys;
    }

    /**
     * @return string[]
     */
    public function getColumnList()
    {
        return $this->columnList;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        if ($this->isFetchDone) {
            throw new BadMethodCallException('cannot set properties.');
        }
        $this->isFetched  = true;
        $this->data[$key] = $value;
        $this->_original_data[$key] = $value;
    }

    /**
     * @param string $id
     */
    public function setPrimaryKeyOnCreatedEntity($id)
    {
        if ($this->isFetched) {
            throw new BadMethodCallException('cannot set primary key on a fetched entity.');
        }
        $this->data[$this->getIdName()] = $id;
        $this->isFetched                = true;
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
            HelperMethods::filterDataByKeys($data, $this->columnList)
        );

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        // find only the key/value that are modified.
        foreach($this->data as $key => $value) {
            if (array_key_exists($key, $this->_original_data) && $value === $this->_original_data) {
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
}