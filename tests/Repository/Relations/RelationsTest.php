<?php
namespace tests\Repository\Relations;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Fixture;
use tests\Utils\Container;
use tests\Utils\Posts;
use tests\Utils\Users;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Repo;
use WScore\Repository\Repository\GenericRepository;

class RelationsTest extends \PHPUnit_Framework_TestCase
{
    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(GenericRepository::class);
        class_exists(PdoQuery::class);
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

    /**
     * @test
     */
    function retrieve_entities_using_hasOne_and_hasMany()
    {
        $c = $this->getFullContainer();
        $fix = $c->getFix();
        $fix->createTables();
        $fix->fillTables();

        $repo = $c->getRepo();

        // get users
        $users = $repo->getRepository('users');
        $user1 = $users->findByKey(1);
        $user2 = $users->findByKey(2);

        // get posts
        $posts = $repo->getRepository('posts');
        $post1 = $posts->findByKey(1);
        $post2 = $posts->findByKey(2);
        $post3 = $posts->findByKey(3);
        $post4 = $posts->findByKey(4);
        // let's check users_id in the post data...
        $this->assertEquals(1, $post1->get('users_id'));
        $this->assertEquals(2, $post2->get('users_id'));
        $this->assertEquals(2, $post3->get('users_id'));
        $this->assertEquals(3, $post4->get('users_id'));

        // retrieve a user from a post entity.
        $hasOne = $repo->hasOne($posts, $users, $post1);
        $this->assertEquals(1, $hasOne->count());
        $post1users = $hasOne->find();
        $this->assertEquals(1, count($post1users));
        $this->assertEquals($user1->get('name'), $post1users[0]->get('name'));

        // retrieve posts from a user entity.
        $hasMany = $repo->hasMany($users, $posts, $user2);
        $this->assertEquals(2, $hasMany->count());
        $user2posts = $hasMany->find();
        $this->assertEquals(2, count($user2posts));
        $this->assertEquals($post2->get('contents'), $user2posts[0]->get('contents'));
        $this->assertEquals($post3->get('contents'), $user2posts[1]->get('contents'));

        // retrieve posts in opposite order.
        /** @var EntityInterface[] $user2posts */
        $user2posts = $hasMany->query()->orderBy('post_id', 'DESC')->find();
        $this->assertEquals(2, count($user2posts));
        $this->assertEquals($post3->get('contents'), $user2posts[0]->get('contents'));
        $this->assertEquals($post2->get('contents'), $user2posts[1]->get('contents'));
    }

    /**
     * @test
     */
    function relate()
    {
        $c = $this->getFullContainer();
        $fix = $c->getFix();
        $fix->createTables();
        $fix->fillTables();

        $repo = $c->getRepo();

        /**
         * get users and posts.
         */

        // get users
        $users = $repo->getRepository('users');
        $user1 = $users->findByKey(1);
        $user2 = $users->findByKey(2);

        // get posts
        $posts = $repo->getRepository('posts');
        $post2 = $posts->findByKey(2); // should belong to user2
        $this->assertEquals(2, $post2->get('users_id'));

        /**
         * put $post2 as $user1's post.
         */
        // relate them.
        $hasMany = $repo->hasMany($users, $posts, $user1);
        $this->assertEquals(1, $hasMany->count());

        $hasMany->relate($post2);
        $this->assertEquals(1, $post2->get('users_id'));
        $posts->save($post2);

        $hasMany = $repo->hasMany($users, $posts, $user1);
        $this->assertEquals(2, $hasMany->count());

        /**
         * put $post1 as $user2's post
         */
        // put $post1 to user2
        $post1 = $posts->findByKey(1); // should belong to user1
        $hasOne = $repo->hasOne($posts, $users, $post1);
        $hasOne->relate($user2);
        $this->assertEquals(2, $post1->get('users_id'));
        $posts->save($post1);

        $hasMany = $repo->hasMany($users, $posts, $user1);
        $this->assertEquals(1, $hasMany->count());
    }
}