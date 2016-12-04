<?php
namespace tests\Cases\SimpleId;

use Interop\Container\ContainerInterface;
use tests\Cases\SimpleId\Models\Fixture;
use tests\Cases\SimpleId\Models\Posts;
use tests\Cases\SimpleId\Models\RepoBuilder;
use tests\Cases\SimpleId\Models\Tags;
use tests\Cases\SimpleId\Models\Users;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Assembly\CollectJoin;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;

class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface|Repo
     */
    private $repo;

    /**
     * @var string[][]
     */
    private $tags;

    function setup()
    {
        class_exists(Collection::class);
        class_exists(Join::class);
        class_exists(CollectJoin::class);
        $this->repo = RepoBuilder::get();
        /** @var Fixture $fix */
        $fix = $this->repo->get(Fixture::class);
        $fix->prepare();
        
        $this->tags = [
            1 => [
                1 => ['tag', 'test',],
            ],
            2 => [
                2 => ['blog', 'post'],
                3 => ['blog', 'test'],
            ],
            3 => [],
        ];
    }

    /**
     * @test
     */
    function Collection_eagerly_loads_related_entities()
    {
        /** @var Users $users */
        $users = $this->repo->get('users');
        $collection = $users->collectFor(['id' => [1,2]]);
        $collection->load('posts');
        $collection->load('posts')->load('tags');
        
        $user1 = $collection[0];
        $user2 = $collection[1];
        $this->assertEquals(2, $collection->count());
        $this->assertEquals(1, $user1->getIdValue());
        $this->assertEquals(2, $user2->getIdValue());

        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($user1->posts));
        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($user1->posts[0]->tags));
        
        foreach($collection as $user) {
            foreach($user->getRelatedEntities('posts') as $post) {
                $this->assertEquals($user->getIdValue(), $post->get('user_id'));
                foreach($post->tags as $idx => $tag) {
                    $tag_id = $this->tags[$user->getIdValue()][$post->getIdValue()][$idx];
                    $this->assertEquals($tag_id, $tag->get('id'));
                }
            }
        }
    }

    /**
     * @test
     */
    function Collection_eagerly_loads_relation_in_reverse_order()
    {
        /** @var Tags $users */
        $users = $this->repo->get('tags');
        $collection = $users->collectFor(['id' => ['test', 'tag', 'blog']]);
        $collection->load('posts');

        $this->assertEquals(3, $collection->count());
        $this->assertEquals('blog', $collection[0]->getIdValue());
        $this->assertEquals('tag', $collection[1]->getIdValue());
        $this->assertEquals('test', $collection[2]->getIdValue());
        
        $answer = [
            'test' => [1, 3],
            'tag'  => [1],
            'blog' => [2, 3],
        ];
        foreach($collection as $tag) {
            foreach($tag->posts as $idx => $post) {
                $this->assertEquals($answer[$tag->getIdValue()][$idx], $post->getIdValue());
                $user = $post->getRelatedEntities('user')[0];
                $this->assertEquals($user->getIdValue(), $post->get('user_id'));
            }
        }
    }

    /**
     * @test
     */
    function add_relation_using_lazy_load()
    {
        /** @var Users $users */
        /** @var Posts $posts */
        $users = $this->repo->get('users');
        $posts = $this->repo->get('posts');

        $user2 = $users->findByKey(2);
        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($user2->posts));
        $this->assertEquals(2, count($user2->posts));

        $post = $posts->create(['contents' => 'created post']);
        $user2->posts[] = $post;
        $user2->save();
        $post->save();

        $user2 = $users->findByKey(2);
        $this->assertEquals(3, count($user2->posts));
        foreach($user2->posts as $post) {
            $this->assertEquals(2, $post->get('user_id'));
        }
    }

    /**
     * @test
     */
    function add_relation_using_eager_loaded_Collection()
    {
        /** @var Users $users */
        /** @var Posts $posts */
        $users = $this->repo->get('users');
        $posts = $this->repo->get('posts');

        $collection = $users->collectByKey(2);
        $collection->load('posts');
        $user2 = $collection[0];
        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($user2->posts));
        $this->assertEquals(2, count($user2->posts));

        $post = $posts->create(['contents' => 'created post']);
        $user2->posts->relate($post);
        $user2->save();
        $post->save();

        $user2 = $users->findByKey(2);
        $this->assertEquals(3, count($user2->posts));
        foreach($user2->posts as $post) {
            $this->assertEquals(2, $post->get('user_id'));
        }
    }

    /**
     * @test
     */
    function transaction_rollbacks_when_exception_is_thrown()
    {
        /** @var Users $users */
        $repo  = $this->repo;
        $users = $this->repo->get('users');
        $user1 = $users->findByKey(1);
        
        $this->assertEquals('name-1', $user1->name);

        $repo->transaction()->run(function () use($user1) {
            $user1->fill(['name' => 'test transaction']);
            $user1->save();
        });

        $user1 = $users->findByKey(1);
        $this->assertEquals('test transaction', $user1->name);

        try {

            $repo->transaction()->run(function () use($user1) {
                $user1->fill(['name' => 'rollback transaction']);
                $user1->save();
                throw new \Exception();
            });

        } catch (\Exception $e) {}

        $user1 = $users->findByKey(1);
        $this->assertEquals('test transaction', $user1->name);
    }

    /**
     * @test
     */    
    function hasMany_relation_setCondition()
    {
        /** @var Users $users */
        $users = $this->repo->get('users');
        $collection   = $users->collectFor([]);

        $collection->load('tests');
        foreach($collection as $user) {
            foreach($user->getRelatedEntities('tests') as $post) {
                $this->assertEquals('test', $post->get('category'));
            }
        }

        $collection->load('orm');
        foreach($collection as $user) {
            foreach($user->getRelatedEntities('orm') as $post) {
                $this->assertEquals('orm', $post->get('category'));
            }
        }
    }

    /**
     * @test
     */
    function BelongTo_relation_setCondition()
    {
        /** @var Posts $posts */
        $posts = $this->repo->get('posts');
        $collection   = $posts->collectFor([]);

        $collection->load('male');
        foreach($collection as $post) {
            $users = $post->getRelatedEntities('male');
            if (!$users) continue;
            foreach($users as $user) {
                $this->assertEquals('M', $user->get('gender'));
            }
        }

        $collection->load('female');
        foreach($collection as $post) {
            $users = $post->getRelatedEntities('female');
            if (!$users) continue;
            foreach($users as $user) {
                $this->assertEquals('F', $user->get('gender'));
            }
        }
    }

    /**
     * @test
     */
    function Join_relation_setCondition()
    {
        /** @var Users $users */
        $users = $this->repo->get('users');
        $collection = $users->collectFor(['id' => [1,2]]);
        $collection->load('posts');
        $collection->load('posts')->load('testBlog');

        foreach($collection as $user) {
            foreach($user->getRelatedEntities('posts') as $post) {
                $this->assertEquals($user->getIdValue(), $post->get('user_id'));
                $tags = $post->testBlog;
                if (!$tags) continue;
                foreach($tags as $idx => $tag) {
                    $tag_id = $tag->getIdValue();
                    $this->assertContains($tag_id, ['test', 'blog']);
                }
            }
        }
    }

    /**
     * @test
     */
    function scopeMale_returns_only_male_users()
    {
        /** @var Users $users */
        $users = $this->repo->get('users');
        $list = $users->scope('males')->find([]);
        $this->assertEquals(2, count($list));
        foreach($list as $u) {
            $this->assertEquals('M', $u->gender);
        }
        // make sure scope would not affect original $users repository. 
        $list = $users->find([]);
        $this->assertEquals(4, count($list));
        $genders = [];
        foreach($list as $u) {
            $g = $u->gender;
            if (isset($genders[$g])) {
                $genders[$g] += 1;
            } else {
                $genders[$g] = 1;
            }
        }
        $this->assertEquals(2, $genders['M']);
        $this->assertEquals(2, $genders['F']);
    }
}