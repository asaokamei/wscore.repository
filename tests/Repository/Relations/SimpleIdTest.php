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
    
    public function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
        
        $this->c = $this->getFullContainer();
        $this->f = $this->c->get(Fixture::class);
        $this->f->createTables();
        $this->f->fillTables(4);
        $this->repo = $repo = $this->c;
        $repo->getRepository('users', ['id'], true);
        $repo->getRepository('posts', ['id'], true);
        $repo->getRepository('user_post', ['user_id', 'post_id']);
    }
    
    /**
     * @return ContainerInterface|Repo
     */
    public function getFullContainer()
    {
        $c    = new Repo();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });
        $c->set(Fixture::class, function (Repo $c) {
            return new Fixture($c->get(PDO::class));
        });

        return $c;
    }

    /**
     * @test
     */
    public function test_hasOne()
    {
        $users = $this->repo->getRepository('users');
        $user2 = $users->findById(2);
        $this->assertEquals(2, $user2->getIdValue());
        
        $hasMany = $this->repo->hasMany($users, 'posts', ['id' => 'user_id'])->withEntity($user2);
        $this->assertEquals(2, $hasMany->count());
        
        $posts = $hasMany->collect();
        $this->assertEquals(2, $posts[0]->get('user_id'));
        $this->assertEquals(2, $posts[1]->get('user_id'));
    }
    
    public function test_hasMany()
    {
        $posts = $this->repo->getRepository('posts');
        $post3 = $posts->findById(3);
        $this->assertEquals(3, $post3->getIdValue());
        $this->assertEquals(2, $post3->get('user_id'));
        
        $hasOne = $this->repo->belongsTo($posts, 'users', ['user_id' => 'id'])->withEntity($post3);
        $this->assertEquals(1, $hasOne->count());

        $users = $hasOne->collect();
        $this->assertEquals(2, $users[0]->getIdValue());
    }
    
    public function test_join()
    {
        $users = $this->repo->getRepository('users');
        $user1 = $users->findById(1);
        $this->assertEquals(1, $user1->getIdValue());

        $join = $this->repo->join($users, 'posts', 'user_post', [
            'id' => 'user_id'
        ], [
            'post_id' => 'id'
        ])->withEntity($user1);
        $this->assertEquals(2, $join->count());

        $joins = $join->queryJoin()->find();
        $this->assertEquals(1, $joins[0]->get('user_id'));
        $this->assertEquals(1, $joins[1]->get('user_id'));
        $this->assertEquals(1, $joins[0]->get('post_id'));
        $this->assertEquals(2, $joins[1]->get('post_id'));

        $posts = $join->collect();
        $this->assertEquals(1, $posts[0]->getIdValue());
        $this->assertEquals(2, $posts[1]->getIdValue());
    }
}