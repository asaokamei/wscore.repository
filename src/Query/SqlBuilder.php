<?php
namespace WScore\Repository\Query;

class SqlBuilder
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @var int
     */
    private $value_count = 1;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $where = [];

    /**
     * @var array
     */
    private $orderBy = [];

    /**
     * SqlBuilder constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $table
     * @return SqlBuilder
     */
    public static function forge($table)
    {
        $self = new self();
        $self->table = $table;
        
        return $self;
    }

    /**
     * @return array
     */
    public function getBindData()
    {
        return $this->params;
    }

    /**
     * returns the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * sets where statement from [$column_name => $value, ] to
     * WHERE $column_name = $value AND ...
     *
     * @param array $where
     * @return self
     */
    public function setWhere(array $where)
    {
        if (!empty($where)) {
            $this->where = array_merge($this->where, $where);
        }
        return $this;
    }

    /**
     * sets the order by clause when select.
     *
     * @param string $order
     * @param string $direction
     * @return self
     */
    public function orderBy($order, $direction = 'ASC')
    {
        $this->orderBy[] = [$order, $direction];
        return $this;
    }

    /**
     * @return string
     */
    public function makeSelect()
    {
        $table = $this->makeTable();
        $where = $this->makeWhere();
        $order = $this->makeOrder();
        $sql   = "SELECT * FROM {$table}{$where}{$order}";

        return $sql;
    }

    /**
     * @return string
     */
    public function makeCount()
    {
        $table = $this->makeTable();
        $where = $this->makeWhere();
        $order = $this->makeOrder();
        $sql   = "SELECT COUNT(*) AS count FROM {$table}{$where}{$order}";

        return $sql;
    }

    /**
     * @param array $data
     * @return string
     */
    public function makeInsert($data)
    {
        $table  = $this->makeTable();
        $into   = [];
        $values = [];
        foreach ($data as $column => $v) {
            $into[]   = $column;
            $values[] = $this->getHolderName($v);
        }
        $into   = implode(', ', $into);
        $values = implode(', ', $values);
        $sql    = "INSERT INTO {$table} ({$into}) VALUES ({$values});";
        
        return $sql;
    }

    /**
     * @param array $data
     * @return string
     */
    public function makeUpdate($data)
    {
        $table = $this->makeTable();
        $sets  = [];
        foreach ($data as $column => $v) {
            $sets[] = "{$column} = " . $this->getHolderName($v);
        }
        $sets  = implode(', ', $sets);
        $where = $this->makeWhere();
        $sql   = "UPDATE {$table} SET {$sets}{$where};";
        
        return $sql;
    }

    /**
     * @return string
     */
    public function makeDelete()
    {
        $table = $this->makeTable();
        $where = $this->makeWhere();
        $sql   = "DELETE FROM {$table}{$where};";
        
        return $sql;
    }

    /**
     * @return string
     */
    private function makeTable()
    {
        if (!$table = $this->getTable()) {
            throw new \InvalidArgumentException('must have table name for sql.');
        }

        return $table;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function getHolderName($value)
    {
        $holder                = "holder_" . $this->value_count++;
        $this->params[$holder] = $value;

        return ':' . $holder;
    }

    /**
     * @return string
     */
    private function makeWhere()
    {
        $where = $this->makeWhereList([], $this->where);
        if (empty($where)) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * @param array $where
     * @param array $value
     * @return array
     */
    private function makeWhereList($where, $value)
    {
        if (empty($value)) {
            return $where;
        }
        foreach ($value as $column => $v) {
            if (is_array($v)) {
                $where = is_numeric($column)
                    ? $this->makeWhereOr($where, $v)
                    : $this->makeWhereIn($where, $column, $v);
                continue;
            }
            $where[] = "{$column} = " . $this->getHolderName($v);
        }

        return $where;
    }

    /**
     * @param array  $where
     * @param string $column
     * @param array  $value
     * @return array
     */
    private function makeWhereIn($where, $column, $value)
    {
        if (empty($value)) {
            return $where;
        }
        $list = [];
        foreach($value as $v) {
            $list[] = $this->getHolderName($v);
        }
        $where[] = "{$column} IN ( " . implode(', ', $list) . " )";
        return $where;
    }

    /**
     * @param array $where
     * @param array $value
     * @return array
     */
    private function makeWhereOr($where, $value)
    {
        $wOr = $this->makeWhereList([], $value);
        $where[] = '( ' . implode(' OR ', $wOr) . ' )';

        return $where;
    }

    /**
     * @return string
     */
    private function makeOrder()
    {
        $order   = [];
        foreach ($this->orderBy as $spec) {
            $column  = $spec[0];
            $dir     = $spec[1];
            $order[] = "{$column} {$dir}";
        }

        if (empty($order)) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $order);
    }
}