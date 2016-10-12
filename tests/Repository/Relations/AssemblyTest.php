<?php
namespace tests\Repository\Relations;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use tests\Utils\Repo\Fixture;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\Users;
use WScore\Repository\Relations\Assembly;
use WScore\Repository\Repo;

class AssemblyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Repo
     */
    private $repo;

    function setup()
    {
        class_exists(Assembly::class);
        $c   = $this->getFullContainer();
        $fix = $c->getFix();
        $fix->createTables();
        $fix->fillTables();
        $this->repo = $c->getRepo();

        // prepare for test. need to pre-create tags repository. 
        $tags  = $this->repo->getRepository('tags', ['tag_id']);
        $posts = $this->repo->getRepository('posts', ['post_id'], true);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $postTag = $this->repo->getJoinRepository('posts_tags', $posts, $tags);
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
        $c->set('users', function(ContainerInterface $c ) {
            return new Users($c->get(Repo::class));
        });
        $c->set('posts', function(ContainerInterface $c ) {
            return new Posts($c->get(Repo::class));
        });
        $c->set(Repo::class, function(ContainerInterface $c) {
            return new Repo($c);
        });

        return $c;
    }
    
    function test0()
    {
        /** @var Users $users */
        $users = $this->repo->getRepository('users');
        $userToPost = $this->repo->hasMany('users', 'posts');

        $user1 = $users->findByKey(1);
        $user1Posts = $userToPost->withEntity($user1)->find();
        $this->assertEquals(1, count($user1Posts));

        $user2 = $users->findByKey(2);
        $user2Posts = $userToPost->withEntity($user2)->find();
        $this->assertEquals(2, count($user2Posts));
        
        $posts = $this->repo->getRepository('posts');
        $allPosts = $posts->find([]);
        $this->assertEquals(4, count($allPosts));

        $asm = new Assembly($users);
        $asm->setRelated('posts', $allPosts, $userToPost);
        $this->assertEquals($user1Posts, $asm->get('posts', $user1));
        $this->assertEquals($user2Posts, $asm->get('posts', $user2));
    }
}