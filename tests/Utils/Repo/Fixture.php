<?php
namespace tests\Utils\Repo;

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
        $this->createTags();
        $this->createPostsTags();
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
        $this->insertTags();
        $this->insertPostsTags();
    }

    /**
     * create users table
     */
    public function createUsers()
    {
        $create  /** @lang SQLite */
            =<<<SQL
CREATE TABLE users (
    users_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL UNIQUE,
    gender      INTEGER,
    created_at  DATETIME,
    updated_at  DATETIME
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * create posts table
     */
    public function createPosts()
    {
        $create  /** @lang SQLite */
              =<<<SQL
CREATE TABLE posts (
    post_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    users_id    INTEGER,
    publish_at  DATETIME,
    contents    VARCHAR(256),
    created_at  DATETIME,
    updated_at  DATETIME
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

    /**
     * @param int $count
     */
    public function insertPosts($count = 4)
    {
        $insert =<<<SQL
INSERT INTO posts (users_id, contents, created_at, updated_at) VALUES (?, ?, ?, ?);
SQL;
        $now  = (new \DateTime())->format('Y-m-d H:i:s');
        foreach(range(1, $count) as $idx) {
            $rec = [
                (int) (($idx/2) + 1),
                'contents for post #'.$idx,
                $now,
                $now,
            ];
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }

    /**
     * ------------------------------------------------------------------------
     * create posts table
     */
    public function createTags()
    {
        $create  /** @lang SQLite */
              =<<<SQL
CREATE TABLE tags (
    tag_id      VARCHAR(32),
    tag         VARCHAR(64),
    created_at  DATETIME,
    updated_at  DATETIME
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * 
     */
    public function insertTags()
    {
        $insert =<<<SQL
INSERT INTO tags (tag_id, tag, created_at, updated_at) VALUES (?, ?, ?, ?);
SQL;
        $now  = (new \DateTime())->format('Y-m-d H:i:s');
        $tags = [
            ['test', 'test tag', $now, $now],
            ['tag',  'tagged', $now, $now],
            ['blog', 'blogging', $now, $now],
            ['post', 'posting', $now, $now],
        ];
        $stmt = $this->pdo->prepare($insert);
        foreach($tags as $rec) {
            $stmt->execute($rec);
        }
    }

    /**
     * ------------------------------------------------------------------------
     * create posts table
     */
    public function createPostsTags()
    {
        $create  /** @lang SQLite */
              =<<<SQL
CREATE TABLE posts_tags (
    posts_tags_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    posts_post_id INTEGER,
    tags_tag_id VARCHAR(32),
    created_at  DATETIME
);
SQL;
        $this->pdo->exec($create);
    }

    /**
     * 
     */
    public function insertPostsTags()
    {
        $insert =<<<SQL
INSERT INTO posts_tags (posts_post_id, tags_tag_id, created_at) VALUES (?, ?, ?);
SQL;
        $now  = (new \DateTime())->format('Y-m-d H:i:s');
        $list = [
            [1, 'test', $now],
            [1, 'tag',  $now],
            [2, 'blog', $now],
            [3, 'post', $now],
        ];
        foreach($list as $rec) {
            $stmt = $this->pdo->prepare($insert);
            $stmt->execute($rec);
        }
    }
}