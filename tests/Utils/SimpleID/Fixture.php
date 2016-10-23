<?php
namespace tests\Utils\SimpleID;

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
        $this->createPosts();
        $this->createUserPost();
    }

    /**
     * fill tables by inserting data
     *
     * @param int $count
     */
    public function fillTables($count = 4)
    {
        $this->insertUsers($count);
        $this->insertPosts($count);
        $this->insertUserPost();
    }

    /** ------------------------------------------------------------------------
     * create users table
     */
    public function createUsers()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL UNIQUE,
    gender      INTEGER
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * @param int $count
     */
    public function insertUsers($count = 4)
    {
        $insert =<<<SQL
INSERT INTO users (name, gender) VALUES (?, ?);
SQL;
        $sex  = ['M', 'F'];
        foreach(range(1, $count) as $idx) {
            $rec = [
                'name-' . $idx,
                $sex[$idx % 2],
            ];
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }

    /** ------------------------------------------------------------------------
     * create posts table
     */
    public function createPosts()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE posts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER,
    publish_at  DATETIME,
    contents    VARCHAR(256)
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * @param int $count
     */
    public function insertPosts($count = 4)
    {
        $insert =<<<SQL
INSERT INTO posts (user_id, contents) VALUES (?, ?);
SQL;
        foreach(range(1, $count) as $idx) {
            $rec = [
                (int) (($idx/2) + 1),
                'contents for post #'.$idx,
            ];
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }

    /** ------------------------------------------------------------------------
     * create posts table
     */
    public function createUserPost()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE user_post (
    user_id     INTEGER,
    post_id     INTEGER
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     *
     */
    public function insertUserPost()
    {
        $insert =<<<SQL
INSERT INTO user_post (user_id, post_id) VALUES (?, ?);
SQL;
        $list = [
            [1, 1],
            [1, 2],
            [2, 2],
            [3, 3],
        ];
        foreach($list as $rec) {
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }
}