<?php
namespace tests\Cases\SimpleId\Models;

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
    
    public function prepare()
    {
        $this->createTables();
        $this->fillTables(4);
    }

    /**
     * creates tables
     */
    private function createTables()
    {
        $this->createUsers();
        $this->createPosts();
        $this->createTags();
        $this->createPostsTags();
    }

    /**
     * fill tables by inserting data
     *
     * @param int $count
     */
    private function fillTables($count = 4)
    {
        $this->insertUsers($count);
        $this->insertPosts($count);
        $this->insertTags();
        $this->insertPostsTags();
    }

    /** ------------------------------------------------------------------------
     * create users table
     */
    private function createUsers()
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
    private function insertUsers($count = 4)
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
     * 
     * user_id   post_id
     *   1    ->   1
     *   2    ->   2, 3
     *   3    ->   4
     */
    private function createPosts()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE posts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER,
    contents    VARCHAR(256)
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * @param int $count
     */
    private function insertPosts($count = 4)
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

    /**
     * ------------------------------------------------------------------------
     * create tags table
     */
    private function createTags()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE tags (
    id          VARCHAR(32),
    tag         VARCHAR(64)
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     *
     */
    private function insertTags()
    {
        $insert =<<<SQL
INSERT INTO tags (id, tag) VALUES (?, ?);
SQL;
        $tags = [
            ['test', 'test tag'],
            ['tag',  'tagged'],
            ['blog', 'blogging'],
            ['post', 'posting'],
        ];
        $stmt = $this->pdo->prepare($insert);
        foreach($tags as $rec) {
            $stmt->execute($rec);
        }
    }

    /**
     * ------------------------------------------------------------------------
     * create postsTags table
     * 
     * post_id   tag_id
     *   1     -> test, tag
     *   2     -> blog, post,
     *   3     -> test, blog
     * 
     * user_id   post_id    tag_id
     *   1    ->   1      -> test, tag
     *   2    ->   2, 3   -> blog, post, test, blog
     *   3    ->   4
     */
    private function createPostsTags()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE posts_tags (
    post_id     INTEGER,
    tag_id      VARCHAR(32)
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     *
     */
    private function insertPostsTags()
    {
        $insert =<<<SQL
INSERT INTO posts_tags (post_id, tag_id) VALUES (?, ?);
SQL;
        $list = [
            [1, 'test'],
            [1, 'tag' ],
            [2, 'blog'],
            [2, 'post'],
            [3, 'test'],
            [3, 'blog'],
        ];
        foreach($list as $rec) {
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }
}