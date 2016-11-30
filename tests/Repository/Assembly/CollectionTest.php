<?php
namespace tests\Repository\Assembly;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use WScore\Repository\Assembly\CollectHasSome;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Assembly\CollectionInterface;
use WScore\Repository\Assembly\CollectJoin;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\Repository;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $c;

    /**
     * @var CollectionInterface
     */
    private $users;

    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
        class_exists(Join::class);
        class_exists(CollectHasSome::class);
        class_exists(CollectJoin::class);

        $this->c = $this->getFullContainer();
        $this->setDb($this->c->get(PDO::class));
        $this->users = $this->c->get('u');
        $this->users->find([]);
    }

    /**
     * @return ContainerInterface
     */
    function getFullContainer()
    {
        $c = new Repo();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $c->set('u', function(Repo $c) {
            $users = new Collection($c->getRepository('users', ['user_id']));
            return $users;
        });

        return $c;
    }

    function setDb(PDO $pdo)
    {
        $create /** @lang SQLite */
            = <<<SQL
CREATE TABLE users (
    user_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL,
    gender      INTEGER,
    score       INTEGER
);
SQL;
        $pdo->exec($create);

        /** @noinspection SqlResolve */
        $insert = <<<SQL
INSERT INTO users (user_id, name, gender, score) VALUES (?, ?, ?, ?);
SQL;
        $list   = [
            [1, 'test', 'male', 10],
            [2, 'more', 'female', 20],
            [3, 'done', 'male', 30],
            [4, 'test', 'female', 40],
        ];
        $stmt   = $pdo->prepare($insert);
        foreach ($list as $rec) {
            $stmt->execute($rec);
        }
    }

    /**
     * @test
     */
    function count_returns_number_of_entities()
    {
        $users = $this->users;
        $this->assertEquals(4, $users->count());
    }

    /**
     * @test
     */
    function filter_creates_subset_of_new_collection()
    {
        $users = $this->users;
        $females = $users->filter(function($entity) {
            return $entity->gender == 'female';
        });
        $this->assertEquals(4, $users->count());
        $this->assertEquals(2, $females->count());
    }

    /**
     * @test
     */
    function walk_alters_all_entities()
    {
        $users = $this->users;
        $users->walk(function (EntityInterface $entity) {
            $entity->fill(['score' => 100]);
        });
        foreach($users as $user) {
            $this->assertEquals(100, $user->score);
        }
    }

    /**
     * @test
     */
    function map_will_extract_array()
    {
        $users = $this->users;
        $scores = $users->map(function (EntityInterface $entity) {
            return $entity->get('score');
        });
        $this->assertEquals([10, 20, 30, 40], $scores);
        
        $scores = $users->column('score');
        $this->assertEquals([10, 20, 30, 40], $scores);
    }

    /**
     * @test
     */
    function sum_max_min_return_some_int()
    {
        $users = $this->users;
        $this->assertEquals(100, $users->sum('score'));
        $this->assertEquals(40, $users->max('score'));
        $this->assertEquals(10, $users->min('score'));
    }
}
