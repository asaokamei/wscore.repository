<?php
namespace WScore\Repository\Helpers;

use PDO;

class Transaction
{
    /**
     * @var PDO[]
     */
    private $pdoList = [];

    /**
     * Transaction constructor.
     *
     * @param PDO[] $pdoList
     */
    public function __construct($pdoList)
    {
        $this->pdoList = $pdoList;
    }

    /**
     * @param callable $task
     * @throws \Exception
     */
    public function run(callable $task)
    {
        $this->begin();
        try {
            
            call_user_func($task);

        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        $this->commit();
    }

    /**
     *
     */
    public function begin()
    {
        foreach ($this->pdoList as $pdo) {
            $pdo->beginTransaction();
        }
    }

    /**
     *
     */
    public function commit()
    {
        foreach ($this->pdoList as $pdo) {
            $pdo->commit();
        }
    }

    /**
     *
     */
    public function rollback()
    {
        foreach ($this->pdoList as $pdo) {
            $pdo->rollBack();
        }
    }
}