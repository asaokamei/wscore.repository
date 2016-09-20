<?php
namespace WScore\Repository\Query;

use Aura\SqlQuery\Common\OrderByInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\WhereInterface;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface as AuraQueryInterface;
use PDO;
use PDOStatement;

class AuraBuilder
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var QueryFactory
     */
    private $factory;

    private $info = [];

    /**
     * AuraBuilder constructor.
     *
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo     = $pdo;
        $this->factory = new QueryFactory($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    /**
     * @return $this
     */
    public function clean()
    {
        $this->info = [
            'table' => '',
        ];

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->info[$key] = $value;
    }

    /**
     * @param string      $key
     * @param array|mixed $value
     */
    public function merge($key, $value)
    {
        $original = isset($this->info[$key]) ? $this->info[$key] : [];
        $this->info[$key] = array_merge($original, (array)$value);
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->info) ? $this->info[$key] : $default;
    }

    /**
     * @return PDOStatement
     */
    public function execSelect()
    {
        return $this->prepareSelect('*');
    }

    /**
     * @return int
     */
    public function execCount()
    {
        $statement = $this->prepareSelect('COUNT(*) AS count');

        return (int)$statement->fetch(PDO::FETCH_COLUMN, 1);
    }

    /**
     * @param string $col
     * @return PDOStatement
     */
    private function prepareSelect($col)
    {
        $table = $this->get('table');

        $select = $this->factory->newSelect();
        $select->from($table)
            ->cols([$col]);

        $this->setupWhere($select);
        $this->setupOrder($select);
        $this->setupJoin($select);

        $statement = $this->prepareAndExecutePdo($select);

        return $statement;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function execInsert(array $data)
    {
        $table = $this->get('table');

        $insert = $this->factory->newInsert();
        $insert->into($table)
            ->cols($data);

        return $this->executeQuery($insert);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function execUpdate($data)
    {
        $table = $this->get('table');

        $update = $this->factory->newUpdate();
        $update->table($table)
            ->cols($data);

        $this->setupWhere($update);

        return $this->executeQuery($update);
    }

    /**
     * @return bool
     */
    public function execDelete()
    {
        $table = $this->get('table');

        $delete = $this->factory->newDelete();
        $delete->from($table);
        $this->setupWhere($delete);

        return $this->executeQuery($delete);
    }

    /**
     * @param WhereInterface $sql
     */
    private function setupWhere(WhereInterface $sql)
    {
        $where = $this->get('conditions', []);

        foreach ($where as $column => $value) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $sql->where("{$column} = ?", $value);
        }
    }

    /**
     * @param OrderByInterface $sql
     */
    private function setupOrder(OrderByInterface $sql)
    {
        $order = $this->get('orderBy', []);
        foreach ($order as $item) {
            $sql->orderBy(["{$item[0]} {$item[1]}"]);
        }
    }

    /**
     * @param SelectInterface $select
     */
    private function setupJoin(SelectInterface $select)
    {
        $joins = $this->get('join', []);
        foreach ($joins as $item) {
            $table = $item[0];
            $using = [];
            foreach ($item[1] as $col1 => $col2) {
                $using[] = "{$col1}={$col2}";
            }
            $using = implode(' AND ', $using);
            $select->join('INNER', $table, $using);
        }
    }

    /**
     * @param AuraQueryInterface $select
     * @return PDOStatement
     */
    private function prepareAndExecutePdo($select)
    {
        $sql       = $select->getStatement();
        $parameter = $select->getBindValues();
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameter);

        return $statement;
    }

    /**
     * @param AuraQueryInterface $update
     * @return bool
     */
    private function executeQuery($update)
    {
        $sql       = $update->getStatement();
        $parameter = $update->getBindValues();
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($parameter);
    }
}