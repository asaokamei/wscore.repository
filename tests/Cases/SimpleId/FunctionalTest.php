<?php
namespace tests\Cases\SimpleId;

use Interop\Container\ContainerInterface;
use tests\Cases\SimpleId\Models\Fixture;
use tests\Cases\SimpleId\Models\Services;
use tests\Cases\SimpleId\Models\Tags;
use tests\Cases\SimpleId\Models\Users;
use WScore\Repository\Assembly\Collection;
use WScore\Repository\Assembly\CollectJoin;
use WScore\Repository\Relations\Join;

class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $c;

    /**
     * @var string[][]
     */
    private $tags;

    function setup()
    {
        class_exists(Collection::class);
        class_exists(Join::class);
        class_exists(CollectJoin::class);
        $this->c = Services::get();
        /** @var Fixture $fix */
        $fix = $this->c->get(Fixture::class);
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
    
    function test()
    {
        /** @var Users $users */
        $users = $this->c->get('users');
        $collection = $users->collectFor(['id' => [1,2]]);
        $collection->load('posts');
        $collection->load('posts')->load('tags');
        
        $user1 = $collection[0];
        $user2 = $collection[1];
        $this->assertEquals(2, $collection->count());
        $this->assertEquals(1, $user1->getIdValue());
        $this->assertEquals(2, $user2->getIdValue());

        $this->assertEquals('WScore\Repository\Assembly\Collection', get_class($user1->posts));
        
        foreach($collection as $user) {
            foreach($user->posts as $post) {
                $this->assertEquals($user->getIdValue(), $post->get('user_id'));
                foreach($post->tags as $idx => $tag) {
                    $tag_id = $this->tags[$user->getIdValue()][$post->getIdValue()][$idx];
                    $this->assertEquals($tag_id, $tag->get('id'));
                }
            }
        }
    }
    
    function test_reverse()
    {
        /** @var Tags $users */
        $users = $this->c->get('tags');
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
                $user = $post->user[0];
                $this->assertEquals($user->getIdValue(), $post->get('user_id'));
            }
        }
    }
}