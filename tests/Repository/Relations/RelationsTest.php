<?php
namespace tests\Repository\Relations;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Repo\Fixture;
use tests\Utils\Container;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\Users;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Repo;
use WScore\Repository\Repository\Repository;

class RelationsTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(PdoQuery::class);
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
        $c->set('users', function(Repo $c ) {
            return new Users($c);
        });
        $c->set('posts', function(Repo $c ) {
            return new Posts($c);
        });

        return $c;
    }

    /**
     * @test
     */
    public function retrieve_entities_using_hasOne_and_hasMany()
    {
        $c = $this->getFullContainer();
        $fix = $c->get(Fixture::class);
        $fix->createTables();
        $fix->fillTables();

        $repo = $c;

        // get users
        $users = $repo->getRepository('users');
        $user1 = $users->findById(1);
        $user2 = $users->findById(2);

        // get posts
        $posts = $repo->getRepository('posts');
        $post1 = $posts->findById(1);
        $post2 = $posts->findById(2);
        $post3 = $posts->findById(3);
        $post4 = $posts->findById(4);
        // let's check users_id in the post data...
        $this->assertEquals(1, $post1->get('users_id'));
        $this->assertEquals(2, $post2->get('users_id'));
        $this->assertEquals(2, $post3->get('users_id'));
        $this->assertEquals(3, $post4->get('users_id'));

        // retrieve a user from a post entity.
        $hasOne = $repo->belongsTo($posts, $users)->withEntity($post1);
        $this->assertEquals(1, $hasOne->count());
        $post1users = $hasOne->collect();
        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($post1users));
        $this->assertCount(1, $post1users);
        $this->assertEquals($user1->get('name'), $post1users[0]->get('name'));

        // retrieve posts from a user entity.
        $hasMany = $repo->hasMany($users, $posts)->withEntity($user2);
        $this->assertEquals(2, $hasMany->count());
        $user2posts = $hasMany->collect();
        $this->assertCount(2, $user2posts);
        $this->assertEquals($post2->get('contents'), $user2posts[0]->get('contents'));
        $this->assertEquals($post3->get('contents'), $user2posts[1]->get('contents'));

        // retrieve posts in opposite order.
        /** @var EntityInterface[] $user2posts */
        $user2posts = $hasMany->query()->orderBy('post_id', 'DESC')->find();
        $this->assertCount(2, $user2posts);
        $this->assertEquals($post3->get('contents'), $user2posts[0]->get('contents'));
        $this->assertEquals($post2->get('contents'), $user2posts[1]->get('contents'));
    }

    /**
     * @test
     */
    public function relate()
    {
        $c = $this->getFullContainer();
        $fix = $c->get(Fixture::class);
        $fix->createTables();
        $fix->fillTables();

        $repo = $c;

        /**
         * get users and posts.
         */

        // get users
        $users = $repo->getRepository('users');
        $user1 = $users->findById(1);
        $user2 = $users->findById(2);

        // get posts
        $posts = $repo->getRepository('posts');
        $post2 = $posts->findById(2); // should belong to user2
        $this->assertEquals(2, $post2->get('users_id'));

        /**
         * put $post2 as $user1's post.
         */
        // relate them.
        $hasMany = $repo->hasMany('users', 'posts')->withEntity($user1);
        $this->assertEquals(1, $hasMany->count());

        $hasMany->relate($post2);
        $this->assertEquals(1, $post2->get('users_id'));
        $post2->save();

        $hasMany = $repo->hasMany($users, $posts)->withEntity($user1);
        $this->assertEquals(2, $hasMany->count());

        /**
         * put $post1 as $user2's post
         */
        // put $post1 to user2
        $post1 = $posts->findById(1); // should belong to user1
        $hasOne = $repo->belongsTo('posts', 'users')->withEntity($post1);
        $hasOne->relate($user2);
        $this->assertEquals(2, $post1->get('users_id'));
        $post1->save();

        $hasMany = $repo->hasMany($users, $posts)->withEntity($user1);
        $this->assertEquals(1, $hasMany->count());
    }
}