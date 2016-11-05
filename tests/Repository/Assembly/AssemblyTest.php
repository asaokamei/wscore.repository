<?php
namespace tests\Repository\Assembly;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use tests\Utils\Repo\Fixture;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\PostsTags;
use tests\Utils\Repo\Users;
use WScore\Repository\Assembly\EntityList;
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
        $userList = new EntityList($this->c->get('users'));
        $userList->setEntities([$user2]);
        $postList = $userList->relate('posts');
        $this->assertEquals(2, count($postList->find($user2)));
        foreach($postList->find($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
    }
    
    function test2()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findByKey(2);
        $user3    = $userRepo->findByKey(3);
        $userList = new EntityList($this->c->get('users'));
        $userList->setEntities([$user2, $user3]);
        $postList = $userList->relate('posts');
        $this->assertEquals(2, count($postList->find($user2)));
        $this->assertEquals(1, count($postList->find($user3)));
        foreach($postList->find($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
        foreach($postList->find($user3) as $post) {
            $this->assertEquals('3', $post->get('users_id'));
        }
    }

    /**
     * @test
     */
    function user2_and_user3_hasMany_posts()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findByKey(2);
        $user3    = $userRepo->findByKey(3);
        $userList = new EntityList($userRepo);
        $userList->setEntities([$user2, $user3]);
        $userList->relate('posts');

        $this->assertEquals(2, count($user2->posts));
        $this->assertEquals(1, count($user3->posts));
        foreach($user2->posts as $post) {
            $this->assertEquals('2', $post->users_id);
        }
        foreach($user3->posts as $post) {
            $this->assertEquals('3', $post->users_id);
        }
    }
    
    function test3()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user1    = $userRepo->findByKey(1);
        $userList = new EntityList($this->c->get('users'));
        $userList->setEntities([$user1]);
        $postList = $userList->relate('posts');
        $this->assertEquals(1, count($postList->find($user1)));
        $post = $postList[0];
        $tagsList = $postList->relate('tags');
        $this->assertEquals(2, count($tagsList->find($post)));
    }

    /**
     * @test
     */
    function user1_hasOne_post_and_joined_with_tags()
    {
        /** @var Users $repo */
        $repo  = $this->c->get('users');
        $user1 = $repo->findByKey(1);
        $user2 = $repo->findByKey(2);
        $list  = new EntityList($repo);
        $list->setEntities([$user1, $user2]);

        $list->relate('posts');
        $list->relate('posts')->relate('tags');
        
        $this->assertEquals(1, count($user1->posts));
        $post10 = $user1->posts[0];
        $this->assertEquals(2, count($post10->tags));
        $this->assertEquals('test tag', $post10->tags[0]->tag);
        $this->assertEquals('tagged', $post10->tags[1]->tag);

        $this->assertEquals(2, count($user2->posts));
        $post20 = $user2->posts[0];
        $this->assertEquals(1, count($post20->tags));
        $this->assertEquals('blogging', $post20->tags[0]->tag);
    }
}
