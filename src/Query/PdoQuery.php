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
     * @var array
     */
    private $fetchMode = [PDO::FETCH_ASSOC, null, null];

    /**
     * @var SqlBuilder
     */
    private $builder = null;

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
    public function execute($sql, $data = [])
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt instanceof PDOStatement) {
            $stmt->execute($data);
            $this->applyFetchModeToStmt($stmt);
        }
        return $stmt;
    }

    /**
     * @param PDOStatement $stmt
     */
    private function applyFetchModeToStmt($stmt)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(
            $this->fetchMode[0],
            $this->fetchMode[1],
            $this->fetchMode[2]
        );
    }

    /**
     * sets database table name to query.
     * should return a new object so that the object can be reused safely.
     *
     * @param string $table
     * @return QueryInterface
     */
    public function withTable($table)
    {
        $this->builder = SqlBuilder::forge($table);
        return $this;
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
     * @param int    $mode
     * @param string $fetch_args
     * @param array  $ctor_args
     * @return QueryInterface
     */
    public function setFetchMode($mode, $fetch_args = null, $ctor_args = [])
    {
        $this->fetchMode = [
            $mode,
            $fetch_args, 
            $ctor_args,
        ];
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
        $this->builder->where($condition);

        return $this;
    }
    /**
     * selects and returns as indicated by fetch mode.
     * most likely returns some EntityInterface objects.
     *
     * @param array $keys
     * @return mixed[]|EntityInterface[]
     */
    public function find($keys = [])
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
        $stmt = $this->pdo->prepare($sql);
        if ($stmt instanceof PDOStatement) {
            if ($stmt->execute($data)) {
                $this->applyFetchModeToStmt($stmt);
            }
        }
        return $stmt;
    }

    /**
     * execute select statement with $keys as condition.
     * returns PDOStatement reflecting the self::setFetchMode().
     *
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys = [])
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
    public function count($keys = [])
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
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
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

    /**
     * JOIN clause with another $join table, with $join_on condition.
     * if $join_on is an array (keys are numeric), turns the value to
     * USING clause, for hashed array, turns to ON clause.
     *
     * @param string $join
     * @param array  $join_on
     * @return self
     */
    public function join($join, $join_on)
    {
        $this->builder->join($join, $join_on);
        return $this;
    }
}