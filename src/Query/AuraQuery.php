<?php
namespace WScore\Repository\Query;

use PDO;
use PDOStatement;
use WScore\Repository\Entity\EntityInterface;

class AuraQuery implements QueryInterface
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var AuraBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $fetchMode = [PDO::FETCH_ASSOC, null, null];

    /**
     * AuraQuery constructor.
     *
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->builder = new AuraBuilder($pdo);
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
        $this->builder = $this->builder->clean();
        $this->builder->set('table', $table);
        return $this;
    }

    /**
     * returns the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->builder->get('table');
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
            $ctor_args
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
        $this->builder->merge('conditions', $condition);
        return $this;
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
        $this->builder->merge('orderBy', [[$order, $direction==='ASC' ?: 'DESC']]);
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
     * execute select statement with $keys as condition.
     * returns PDOStatement reflecting the self::setFetchMode().
     *
     * @param array $keys
     * @return PDOStatement
     */
    public function select($keys = [])
    {
        $this->builder->merge('conditions', $keys);
        $stmt = $this->builder->execSelect();

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(
            $this->fetchMode[0],
            $this->fetchMode[1],
            $this->fetchMode[2]
        );
        return $stmt;
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
        return $this->builder->execCount();
    }

    /**
     * insert a data into a database table.
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data)
    {
        return $this->builder->execInsert($data);
    }

    /**
     * returns the last inserted ID.
     *
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
     * @return bool
     */
    public function update(array $keys, array $data)
    {
        $this->condition($keys);
        return $this->builder->execUpdate($data);
    }

    /**
     * deletes the rows selected by $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function delete($keys)
    {
        $this->condition($keys);
        return $this->builder->execDelete();
    }

    /**
     * JOIN clause with another $join table, with $join_on condition.
     * if $join_on is an array (keys are numeric), turns the value to
     * USING clause, for hashed array, turns to ON clause.
     *
     * @param string $join
     * @param array  $join_on
     * @return QueryInterface
     */
    public function join($join, $join_on)
    {
        $this->builder->merge('join', [[$join, $join_on]]);
        return $this;
    }
}