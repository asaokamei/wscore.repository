<?php

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $timestamps = [
        'create_at' => 'created_at',
        'update_at' => 'updated_at',
    ];

    /**
     * sets value object for each column. 
     * The value object is constructed as new ValueObject($value)
     * 
     * @var array     [ column-name  =>  value-object class name]
     */
    protected $valueObjects = [];

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
        if (!isset($this->valueObjects[$key])) {
            return $value;
        }
        if (in_array($key, $this->timestamps)) {
            return new DateTimeImmutable($value);
        }
        $class = $this->valueObjects[$key];
        return new $class($value);
    }
    
    /**
     * Entity constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $this->_filterInput($data, $this->listColumns());
        $this->_addTimeStamps('created_at');
        $this->_addTimeStamps('updated_at');
    }

    /**
     * @return array
     */
    public static function getPrimaryKeyColumns()
    {
        return ['id'];
    }

    /**
     * @return array
     */
    public static function listColumns()
    {
        return [];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function create(array $data)
    {
        return new static($data);
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
        return $this->data;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->_filterInput($this->data, $this->getPrimaryKeyColumns());
    }
}