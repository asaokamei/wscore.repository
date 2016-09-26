<?php
namespace tests\Repository\Relations;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Repo\Fixture;
use tests\Utils\Container;
use tests\Utils\Repo\Posts;
use tests\Utils\Repo\Users;
use WScore\Repository\Entity\AbstractEntity;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Relations\JoinRepository;
use WScore\Repository\Relations\JoinBy;
use WScore\Repository\Repo;
use WScore\Repository\Repository\Repository;

class JoinTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * @var Repo
     */
    private $repo;

    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(JoinRepository::class);
        class_exists(PdoQuery::class);
        class_exists(JoinBy::class);
        class_exists(AbstractEntity::class);

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


    /**
     * @test
     */
    function make_sure_Fixture_works_for_tags_and_posts2tags()
    {
        $repo = $this->repo;

        // setup
        $tags = $repo->getRepository('tags');
        $postTag = $repo->getJoinRepository('posts_tags');
        
        // get all tags. 
        $tag1 =  $tags->find([]);
        $this->assertEquals(4, count($tag1)); // OK
        
        // get one posts2tags entity. 
        $pTag = $postTag->findByKey(1);
        $this->assertEquals(1, $pTag->get('posts_post_id'));
        $this->assertEquals('test', $pTag->get('tags_tag_id'));
    }

    /**
     * @test
     */
    function retrieves_joined_tag_from_a_post()
    {
        $repo  = $this->repo;
        $posts = $repo->getRepository('posts');
        
        // this is the post entity to join from. 
        $post1 = $posts->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);

        // how many joined tags?
        $this->assertEquals(2, $join->count());
        $joinedTags = $join->find(); // get the tags!!!
        $this->assertEquals(2, count($joinedTags));
        $this->assertEquals('tags', $joinedTags[0]->getTable());
        $this->assertEquals('test tag', $joinedTags[0]->get('tag'));
        $this->assertEquals('tagged', $joinedTags[1]->get('tag'));
    }

    /**
     * @test
     */
    function relate_tag_to_a_post()
    {
        $repo = $this->repo;
        
        // this is the post entity to join from. 
        $post1 = $repo->getJoinRepository('posts')->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);

        // get a 'blog' tag from tags.
        $blogTag = $repo->getJoinRepository('tags')->findByKey('blog');
        $this->assertEquals('blog', $blogTag->getIdValue());

        // now relate them!
        $join->relate($blogTag);
        $this->assertEquals(3, $join->count());
        $tags = $join->find();
        $this->assertEquals(3, count($tags));
        $this->assertEquals('blog', $tags[2]->getIdValue());
    }

    /**
     * @test
     */
    function delete_removes_existing_relation()
    {
        $repo = $this->repo;

        // this is the post entity to join from.
        $post1 = $repo->getJoinRepository('posts')->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);
        $this->assertEquals(2, $join->count());

        // delete one tag.
        $joinTags = $join->find();
        $join->delete($joinTags[0]);
        $this->assertEquals(1, $join->count());

        // find the rest of the related tags.
        $joinTag2 = $join->find();
        $this->assertEquals($joinTags[1]->getIdValue(), $joinTag2[0]->getIdValue());
    }

    /**
     * @test
     */
    function clear()
    {
        $repo = $this->repo;

        // this is the post entity to join from.
        $post1 = $repo->getJoinRepository('posts')->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);
        $this->assertEquals(2, $join->count());

        $join->clear();
        $this->assertEquals(0, $join->count());
    }

    /**
     * @test
     */
    function query_works_on_target_table()
    {
        $repo = $this->repo;

        // this is the post entity to join from.
        $post1 = $repo->getJoinRepository('posts')->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);

        $joinTags = $join->query()
            ->find(['tag' => 'tagged']);
        $this->assertEquals(1, count($joinTags));
        $tag1 = $joinTags[0];
        $this->assertEquals('tag', $tag1->get('tags_tag_id'));
        $this->assertEquals('tagged', $tag1->get('tag'));
    }

    /**
     * @test
     */
    function queryJoin_works_on_join_table()
    {
        $repo = $this->repo;

        // this is the post entity to join from.
        $post1 = $repo->getJoinRepository('posts')->findByKey(1);
        $join  = $repo->joinBy('posts', 'tags')->withEntity($post1);

        $joinTags = $join->queryJoin()->find(['tags_tag_id' => 'test']);
        $this->assertEquals(1, count($joinTags));
        $tag1 = $joinTags[0];
        $this->assertEquals('test', $tag1->get('tags_tag_id'));
    }
}