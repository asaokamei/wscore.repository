<?php
namespace tests\Repository\Assembly;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use tests\Utils\Repo\Fixture;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\PostsTags;
use tests\Utils\Repo\Users;
use WScore\Repository\Assembly\Entities;
use WScore\Repository\Assembly\Joined;
use WScore\Repository\Assembly\Related;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Repo;
use WScore\Repository\Repository\Repository;
use WScore\ScoreSql\Sql\Join;

class AssemblyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $c;

    /**
     * @var Fixture
     */
    private $fix;

    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
        class_exists(Join::class);
        class_exists(Related::class);
        class_exists(Joined::class);

        $this->c = $this->getFullContainer();
        $this->fix = $this->c->getFix();
        $this->fix->createTables();
        $this->fix->fillTables();

    }

    /**
     * @return Container
     */
    function getFullContainer()
    {
        $c = new Container();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $c->set(Fixture::class, function (ContainerInterface $c) {
            return new Fixture($c->get(PDO::class));
        });
        $c->set('users', function (ContainerInterface $c) {
            return new Users($c->get(Repo::class));
        });
        $c->set('posts', function (ContainerInterface $c) {
            return new Posts($c->get(Repo::class));
        });
        $c->set('posts_tags', function (ContainerInterface $c) {
            return new PostsTags($c->get(Repo::class));
        });
        $c->set(Repo::class, function (ContainerInterface $c) {
            $repo = new Repo($c);
            $repo->getRepository('tags', ['tag_id'], true);
            return $repo;
        });

        return $c;
    }

    function test()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findByKey(2);
        $userAsm  = new Entities($this->c->get('users'));
        $userAsm->entities([$user2]);
        $relate = $userAsm->relate('posts');
        $this->assertEquals(2, count($relate->find($user2)));
        foreach($relate->find($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
    }
    
    function test2()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findByKey(2);
        $user3    = $userRepo->findByKey(3);
        $userAsm  = new Entities($this->c->get('users'));
        $userAsm->entities([$user2, $user3]);
        $relate = $userAsm->relate('posts');
        $this->assertEquals(2, count($relate->find($user2)));
        $this->assertEquals(1, count($relate->find($user3)));
        foreach($relate->find($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
        foreach($relate->find($user3) as $post) {
            $this->assertEquals('3', $post->get('users_id'));
        }
    }
    
    function test3()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user1    = $userRepo->findByKey(1);
        $userList = new Entities($this->c->get('users'));
        $userList->entities([$user1]);
        $postList = $userList->relate('posts');
        $this->assertEquals(1, count($postList->find($user1)));
        $post = $postList[0];
        $tagsList = $postList->relate('tags');
        $this->assertEquals(2, count($tagsList->find($post)));
    }
}
