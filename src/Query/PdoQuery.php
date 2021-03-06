<?php
namespace WScore\Repository\Query;

use PDO;
use PDOStatement;
use WScore\Repository\Entity\EntityInterface;

class PdoQuery implements QueryInterface 
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var callable
     */
    private $fetchMode;

    /**
     * @var SqlBuilder
     */
    private $builder;

    /**
     * PdoQuery constructor.
     *
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $sql
     * @param array  $data
     * @return PDOStatement
     */
    public function execute($sql, array $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt instanceof PDOStatement) {
            $stmt->execute($data);
            $this->applyFetchModeToStmt($stmt);
        }
        return $stmt;
    }

    /**
     * set fetch mode for PDOStatement. 
     * use fetchMode callable if set, or use FETCH_ASSOC as default. 
     * 
     * @param PDOStatement $stmt
     */
    private function applyFetchModeToStmt($stmt)
    {
        if (is_callable($this->fetchMode)) {
            call_user_func($this->fetchMode, $stmt);
            return;
        }
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
    }

    /**
     * sets database table name to query.
     * should return a new object so that the object can be reused safely.
     *
     * @param string $table
     * @param null|string[]   $orderDefault
     * @return QueryInterface
     */
    public function withTable($table, $orderDefault = null)
    {
        $self = clone $this;
        $self->builder = SqlBuilder::forge($table, $orderDefault);
        
        return $self;
    }

    /**
     * @return QueryInterface
     */
    public function newQuery()
    {
        $self = clone $this;
        $self->builder = clone $self->builder;
        return $self;
    }

    /**
     * returns the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->builder->getTable();
    }

    /**
     * sets the fetch mode of PDOStatement.
     *
     * @param callable $callable
     * @return QueryInterface
     */
    public function setFetchMode(callable $callable)
    {
        $this->fetchMode = $callable;
        return $this;
    }

    /**
     * sets where statement from [$column_name => $value, ] to
     * WHERE $column_name = $value AND ...
     *
     * @param array $condition
     * @return QueryInterface
     */
    public function condition(array $condition)
    {
        if (!empty($condition)) {
            $this->builder->where($condition);
        }

        return $this;
    }
    /**
     * selects and returns as indicated by fetch mode.
     * most likely returns some EntityInterface objects.
     *
     * @param array $keys
     * @return mixed[]|EntityInterface[]
     */
    public function find(array $keys = [])
    {
        return $this->select($keys)->fetchAll();
    }


    /**
     * sets the order by clause when select.
     *
     * @param string $order
     * @param string $direction
     * @return QueryInterface
     */
    public function orderBy($order, $direction = 'ASC')
    {
        $this->builder->orderBy($order, $direction);

        return $this;
    }

    /**
     * @return PDOStatement
     */
    private function execBuilder()
    {
        $sql  = $this->builder->getSql();
        $data = $this->builder->getBindData();
        return $this->execute($sql, $data);
    }

    /**
     * execute select statement with $keys as condition.
     * returns PDOStatement reflecting the self::setFetchMode().
     *
     * @param array $keys
     * @return PDOStatement
     */
    public function select(array $keys = [])
    {
        $this->condition($keys);
        $this->builder->makeSelect();
        return $this->execBuilder();
    }
    
    /**
     * returns the number of rows found.
     * the $keys are same as that of self::select method.
     *
     * @param array $keys
     * @return int
     */
    public function count(array $keys = [])
    {
        $this->condition($keys);
        $this->builder->makeCount();
        $stmt = $this->execBuilder();
        return (int) $stmt->fetchColumn(0);
    }

    /**
     * insert a data into a database table.
     * returns the new ID of auto-increment column if exists,
     * or true if no such column.
     *
     * @param array $data
     * @return string|bool
     */
    public function insert(array $data)
    {
        $this->builder->makeInsert($data);
        return $this->execBuilder();
    }

    /**
     * @param string $table
     * @param string $idName
     * @return string
     */
    public function lastId($table = '', $idName = '')
    {
        $name = null;
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $name = implode( '_', [$table, $idName, 'seq' ] );
        }

        return $this->pdo->lastInsertId($name);
    }

    /**
     * updates the value as $data for rows selected by $keys.
     *
     * @param array $keys
     * @param array $data
     * @return bool|PDOStatement
     */
    public function update(array $keys, array $data)
    {
        $this->builder->where($keys);
        $this->builder->makeUpdate($data);
        return $this->execBuilder();
    }

    /**
     * deletes the rows selected by $keys.
     *
     * @param array $keys
     * @return bool|PDOStatement
     */
    public function delete($keys)
    {
        $this->builder->where($keys);
        $this->builder->makeDelete();
        return $this->execBuilder();
    }
}