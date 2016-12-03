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
                1 => ['test', 'tag'],
            ],
            2 => [
                2 => ['blog', 'post'],
                3 => ['test', 'blog'],
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
        $collection = $users->newCollection();
        $collection->find(['id' => ['test', 'tag', 'blog']]);
        $collection->load('posts');

        $this->assertEquals(3, $collection->count());
        $this->assertEquals('test', $collection[0]->getIdValue());
        $this->assertEquals('tag', $collection[1]->getIdValue());
        $this->assertEquals('blog', $collection[2]->getIdValue());
        
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
}