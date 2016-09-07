<?php

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
     * @var array
     */
    protected $timestamps = [
        'create_at' => 'created_at',
        'update_at' => 'updated_at',
    ];

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
    private $isFetchDone = false;

    /**
     * Entity constructor.
     *
     */
    public function __construct()
    {
        $this->isFetchDone = true;
    }

    /**
     * @return array
     */
    public static function listColumns()
    {
        return [];
    }

    /**
     * @return array
     */
    public static function getPrimaryKeyColumns()
    {
        return ['id'];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function create(array $data)
    {
        $entity = new static();
        $entity->data = $entity->_filterInput($data, $entity->listColumns());
        $entity->_addTimeStamps('created_at');
        $entity->_addTimeStamps('updated_at');
        return $entity;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        if (!$this->isFetchDone) {
            throw new BadMethodCallException('cannot set properties.');
        }
        $this->data[$key] = $value;
        $this->_original_data[$key] = $value;
    }

    /**
     * @param array $input
     * @param array $filter
     * @return array
     */
    protected function _filterInput(array $input, array $filter)
    {
        $output = [];
        if(empty($filter)) {
            return $output;
        }
        foreach($filter as $key => $column) {
            if (is_numeric($key)) {
                $key = $column;
            }
            if (array_key_exists($key, $input)) {
                $output[$column] = $input[$key];
            }
        }
        return $output;
    }

    /**
     * @param string $type
     */
    protected function _addTimeStamps($type)
    {
        if (!isset($this->timestamps[$type])) {
            return;
        }
        $column = $this->timestamps[$type];
        if (isset($this->data[$column])) {
            return;
        }
        $this->data[$column] = (new DateTime('now'))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $key
     * @param string $value
     * @return mixed
     */
    protected function _convertToObject($key, $value)
    {
        if (!isset($this->valueObjectClasses[$key])) {
            return $value;
        }
        if (in_array($key, $this->timestamps)) {
            return new DateTimeImmutable($value);
        }
        $valueObject = $this->valueObjectClasses[$key];
        if (is_callable($valueObject)) {
            return $valueObject($value);
        }
        return new $valueObject($value);
    }
    
    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $value = array_key_exists($key, $this->data) ? $this->data[$key] : null;
        return $this->_convertToObject($key, $value);
    }

    /**
     * @param array $data
     * @return EntityInterface
     */
    public function fill(array $data)
    {
        $self = clone($this);
        $self->data = array_merge($this->data, $this->_filterInput($data, $this->listColumns()));
        $self->_addTimeStamps('updated_at');

        return $self;
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
        return $this->_filterInput($this->data, $this->getPrimaryKeyColumns());
    }

    /**
     * @param EntityInterface $entity
     * @param array           $convert
     */
    public function relate(EntityInterface $entity, $convert = [])
    {
        $keys = $entity->getKeys();
        if (!empty($convert)) {
            foreach($convert as $key => $col) {
                $keys[$col] = $keys[$key];
                unset($keys[$key]);
            }
        }
        $this->data = array_merge($this->data, $keys);
    }
}