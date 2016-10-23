<?php
namespace tests\Repository\Relations;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use tests\Utils\SimpleID\Fixture;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Repo;
use WScore\Repository\Repository\Repository;

class SimpleIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    private $c;

    /**
     * @var Fixture
     */
    private $f;

    /**
     * @var Repo
     */
    private $repo;
    
    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
        
        $this->c = $this->getFullContainer();
        $this->f = $this->c->get(Fixture::class);
        $this->f->createTables();
        $this->f->fillTables(4);
        $this->repo = $repo = $this->c->getRepo();
        $repo->getRepository('users', ['id'], true);
        $repo->getRepository('posts', ['id'], true);
        $repo->getRepository('user_post', ['user_id', 'post_id']);
    }
    
    /**
     * @return Container
     */
    function getFullContainer()
    {
        $c    = new Container();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });
        $c->set(Fixture::class, function (ContainerInterface $c) {
            return new Fixture($c->get(PDO::class));
        });
        $c->set(Repo::class, function(ContainerInterface $c) {
            return new Repo($c);
        });

        return $c;
    }

    /**
     * @test
     */
    function test_hasOne()
    {
        $users = $this->repo->getRepository('users');
        $user2 = $users->findByKey(2);
        $this->assertEquals(2, $user2->getIdValue());
        
        $hasMany = $this->repo->hasMany($users, 'posts', ['id' => 'user_id'])->withEntity($user2);
        $this->assertEquals(2, $hasMany->count());
        
        $posts = $hasMany->find();
        $this->assertEquals(2, $posts[0]->get('user_id'));
        $this->assertEquals(2, $posts[1]->get('user_id'));
    }
    
    function test_hasMany()
    {
        $posts = $this->repo->getRepository('posts');
        $post3 = $posts->findByKey(3);
        $this->assertEquals(3, $post3->getIdValue());
        $this->assertEquals(2, $post3->get('user_id'));
        
        $hasOne = $this->repo->hasOne($posts, 'users', ['user_id' => 'id'])->withEntity($post3);
        $this->assertEquals(1, $hasOne->count());

        $users = $hasOne->find();
        $this->assertEquals(2, $users[0]->getIdValue());
    }
    
    function test_join()
    {
        $users = $this->repo->getRepository('users');
        $user1 = $users->findByKey(1);
        $this->assertEquals(1, $user1->getIdValue());

        $join = $this->repo->join($users, 'posts', 'user_post', [
            'id' => 'user_id'
        ], [
            'id' => 'post_id'
        ])->withEntity($user1);
        $this->assertEquals(2, $join->count());

        $joins = $join->queryJoin()->find();
        $this->assertEquals(1, $joins[0]->get('user_id'));
        $this->assertEquals(1, $joins[1]->get('user_id'));
        $this->assertEquals(1, $joins[0]->get('post_id'));
        $this->assertEquals(2, $joins[1]->get('post_id'));

        $posts = $join->find();
        $this->assertEquals(1, $posts[0]->getIdValue());
        $this->assertEquals(2, $posts[1]->getIdValue());
    }
}