<?php
namespace tests\Repository\Assembly;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use tests\Utils\Repo\Fixture;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\PostsTags;
use tests\Utils\Repo\Users;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Assembly\CollectionInterface;
use WScore\Repository\Assembly\CollectJoin;
use WScore\Repository\Assembly\CollectHasSome;
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

    public function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
        class_exists(Join::class);
        class_exists(CollectHasSome::class);
        class_exists(CollectJoin::class);

        $this->c = $this->getFullContainer();
        $this->fix = $this->c->get(Fixture::class);
        $this->fix->createTables();
        $this->fix->fillTables();

    }

    /**
     * @return ContainerInterface
     */
    public function getFullContainer()
    {
        $c = new Repo();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $c->set(Fixture::class, function (Repo $c) {
            return new Fixture($c->get(PDO::class));
        });
        $c->set('users', function (Repo $c) {
            return new Users($c);
        });
        $c->set('posts', function (Repo $c) {
            return new Posts($c);
        });
        $c->set('posts_tags', function (Repo $c) {
            return new PostsTags($c);
        });

        $c->getRepository('tags', ['tag_id'], true);
        return $c;
    }

    public function test()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findById(2);
        $userList = $userRepo->collectById(2);
        $postList = $userList->load('posts');
        $this->assertCount(2, $postList->getRelatedEntities($user2));
        foreach($postList->getRelatedEntities($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
    }

    /**
     * @test
     */
    public function repository_collect_returns_collection()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $userList = $userRepo->collectFor(['users_id' => 2]);
        
        $postList = $userList->load('posts');
        $this->assertEquals(2, count($postList->getRelatedEntities($userList[0])));
        foreach($postList->getRelatedEntities($userList[0]) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
    }
    
    public function test2()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findById(2);
        $user3    = $userRepo->findById(3);
        $userList = new Collection($this->c->get('users'));
        $userList[0] = $user2;
        $userList[]  = $user3;
        $postList = $userList->load('posts');
        $this->assertEquals(2, count($postList->getRelatedEntities($user2)));
        $this->assertEquals(1, count($postList->getRelatedEntities($user3)));
        foreach($postList->getRelatedEntities($user2) as $post) {
            $this->assertEquals('2', $post->get('users_id'));
        }
        foreach($postList->getRelatedEntities($user3) as $post) {
            $this->assertEquals('3', $post->get('users_id'));
        }
    }

    /**
     * @test
     */
    public function user2_and_user3_hasMany_posts()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user2    = $userRepo->findById(2);
        $user3    = $userRepo->findById(3);
        
        $userList = $userRepo->collectFor(['users_id' => [2,3]]);
        $userList->load('posts');

        $this->assertCount(2, $user2->posts);
        $this->assertCount(1, $user3->posts);
        foreach($user2->getRelatedEntities('posts') as $post) {
            $this->assertEquals('2', $post->users_id);
        }
        foreach($user3->getRelatedEntities('posts') as $post) {
            $this->assertEquals('3', $post->users_id);
        }
    }
    
    public function test3()
    {
        /** @var Users $userRepo */
        $userRepo = $this->c->get('users');
        $user1    = $userRepo->findById(1);
        $userList = new Collection($this->c->get('users'));
        $userList->setEntities([$user1]);
        $postList = $userList->load('posts');
        $this->assertEquals(1, count($postList->getRelatedEntities($user1)));
        $post = $postList[0];
        $tagsList = $postList->load('tags');
        $this->assertEquals(2, count($tagsList->getRelatedEntities($post)));
    }

    /**
     * @test
     */
    public function user1_hasOne_post_and_joined_with_tags()
    {
        /** @var Users $repo */
        $repo  = $this->c->get('users');
        $list  = $repo->collect(/** @lang SQLite */
            'SELECT * FROM users WHERE users_id IN(?, ?);', [1, 2]);

        $list->load('posts');
        $list->load('posts')->load('tags');

        list($user1, $user2) = $list;
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

    /**
     * @test
     */    
    public function entityList_iterates()
    {
        /** @var CollectionInterface $list */
        $list  = $this->c->get('users')->collect('SELECT * FROM users WHERE users_id IN(?, ?);', [1, 3]);

        $idList = [1, 3];
        foreach($list as $entity) {
            $id = array_shift($idList);
            $this->assertEquals($id, $entity->users_id);
        }
        $this->assertEquals(2, count($list));
        $this->assertTrue(isset($list[0]));
        $this->assertTrue(isset($list[1]));
        $this->assertFalse(isset($list[2]));
        
        unset($list[0]);
        $this->assertEquals(1, count($list));
    }
}
