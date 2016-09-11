<?php
namespace WScore\Repository\Query;

use PDO;
use PDOStatement;

class PdoQuery implements QueryInterface 
{
    /**
     * @var PDO
     */
    private $pdo;
    
    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $fetchMode = [PDO::FETCH_ASSOC, null, null];

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $orderBy = [];

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
     * @return SqlBuilder
     */
    private function sql()
    {
        $info = get_object_vars($this);
        return new SqlBuilder($this->pdo, $info);
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
        $this->table = $table;
        return $this;
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
        $this->conditions[] = $condition;
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
        $this->orderBy[] = [$order, $direction];
        return $this;
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
        $stmt = $this->sql()->execSelect();
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
        return $this->sql()->execCount();
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
        return $this->sql()->execInsert($data);
    }

    /**
     * @param array $keys
     * @return string
     */
    public function lastId(array $keys)
    {
        $idName = '';
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
            if (count($keys) === 1) {
                $idName = implode( '_', [ $this->table, $keys[0], 'seq' ] );
            }
        }
        
        return $this->pdo->lastInsertId($idName);
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
        return $this->sql()->execUpdate($data);
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
        return $this->sql()->execDelete();
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
        // TODO: Implement join() method.
    }
}