<?php
namespace tests;

use PDO;

class Fixture
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Fixture constructor.
     *
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * creates tables
     */
    public function createTables()
    {
        $this->createUsers();
    }

    /**
     * fill tables by inserting data
     *
     * @param int $count
     */
    public function fillTables($count = 4)
    {
        $this->insertUsers($count);
    }

    /**
     * 
     */
    public function createUsers()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE users (
    user_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL UNIQUE,
    gender      INTEGER,
    created_at  DATETIME,
    updated_at  DATETIME
);
SQL;
        $stmt = $this->pdo->prepare($create);
        $stmt->execute();
    }

    /**
     * @param int $count
     */
    public function insertUsers($count = 4)
    {
        $insert =<<<SQL
INSERT INTO users (name, gender, created_at, updated_at) VALUES (?, ?, ?, ?);
SQL;
        $now  = (new \DateTime())->format('Y-m-d H:i:s');
        $sex  = ['M', 'F'];
        foreach(range(1, $count) as $idx) {
            $rec = [
                'name-' . $idx,
                $sex[$idx % 2],
                $now, 
                $now,
            ];
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }
}