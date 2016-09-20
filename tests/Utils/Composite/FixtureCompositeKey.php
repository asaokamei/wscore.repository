<?php
namespace tests\Utils\Composite;

use PDO;

class FixtureCompositeKey
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
     * Create SQLite Tables
     */
    public function createTables()
    {
        $create = /** @lang SQLite */
            <<<END_OF_SQL
CREATE TABLE members (
    type  INTEGER NOT NULL,
    code  INTEGER NOT NULL,
    name        VARCHAR(64) NOT NULL UNIQUE,
    created_at  DATETIME,
    updated_at  DATETIME,
    PRIMARY KEY (type, code)
);
END_OF_SQL;

        $this->pdo->exec($create);


        $create = /** @lang SQLite */
        <<<END_OF_SQL
CREATE TABLE fees (
    year    INTEGER NOT NULL,
    type    INTEGER NOT NULL,
    code    INTEGER NOT NULL,
    amount  INT NOT NULL,
    name    VARCHAR(64) NOT NULL,
    PRIMARY KEY (year, type, code)
);
END_OF_SQL;

        $this->pdo->exec($create);

        $create = /** @lang SQLite */
        <<<END_OF_SQL
CREATE TABLE orders (
    member_type INTEGER NOT NULL,
    member_code INTEGER NOT NULL,
    fee_year    INTEGER NOT NULL,
    fee_code    INTEGER NOT NULL,
    created_at  DATETIME,
    PRIMARY KEY (member_type, member_code, fee_year, fee_code)
);
END_OF_SQL;

        $this->pdo->exec($create);
    }

    /**
     * Insert Data into SQLite Tables.
     */
    public function insertData()
    {
        $now = date('Y-m-d H:i:s');

        $inMember = "INSERT INTO members (type, code, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?);";
        $members = [
            [1, 1, 'Main Member', $now, $now,],
            [2, 1, 'Sub Member',  $now, $now,],
            [1, 2, 'Test Member', $now, $now,],
        ];
        $this->insert($inMember, $members);

        $inFees   = "INSERT INTO fees (year, type, code, amount, name) VALUES(?, ?, ?, ?, ?);";
        $fees = [
            [2015, 1, 1, 1000, 'member fee',  ],
            [2015, 1, 2, 100,  'system fee', ],
            [2016, 1, 1, 1100, 'member fee',  ],
            [2016, 1, 2, 200,  'system fee', ],
            [2015, 2, 1, 700, 'sub-member fee',  ],
            [2015, 2, 2, 100,  'system fee', ],
            [2016, 2, 1, 800, 'sub-member fee',  ],
            [2016, 2, 2, 200,  'system fee', ],
        ];
        $this->insert($inFees, $fees);

        $inOrders = "INSERT INTO orders(member_type, member_code, fee_year, fee_code, created_at) VALUES(?, ?, ?, ?, ?);";
        $orders = [
            [1, 1, 2015, 1, $now, ],
            [1, 1, 2016, 1, $now, ],
            [1, 1, 2016, 2, $now, ],
            [1, 2, 2015, 1, $now, ],
            [1, 2, 2016, 1, $now, ],
            [1, 3, 2016, 1, $now, ],
        ];
        $this->insert($inOrders, $orders);

    }

    /**
     * @param string $sql
     * @param array $list
     */
    private function insert($sql, $list)
    {
        $stmt = $this->pdo->prepare($sql);
        foreach($list as $data) {
            $stmt->execute($data);
        }
    }
}