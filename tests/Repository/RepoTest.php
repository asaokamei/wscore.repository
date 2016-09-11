<?php
namespace tests\Repository;

use tests\Utils\Container;
use tests\Utils\Query;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Repository\Repository;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;

class RepoTest extends \PHPUnit_Framework_TestCase 
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
        class_exists(Query::class);
        
        $this->repo = new Repo();
    }

    /**
     * @test
     */
    function repo_returns_generic_repository()
    {
        $dao = $this->repo->getRepository('testing');
        $this->assertEquals(Repository::class, get_class($dao));
        $this->assertEquals('testing', $dao->getTable());
        $this->assertEquals(['testing_id'], $dao->getKeyColumns());
    }

    /**
     * @test
     */
    function entity_has_same_primary_keys_as_generic_repository()
    {
        $dao = $this->repo->getRepository('testing');
        $this->assertEquals(Entity::class, $dao->getEntityClass());
        $entity = $dao->create([]);
        $this->assertEquals(Entity::class, get_class($entity));
        $this->assertEquals($dao->getKeyColumns(), $entity->getPrimaryKeyColumns());
    }

    /**
     * @test
     */
    function Repo_uses_container_to_retrieve_repository()
    {
        $c    = new Container();
        $c->set('testing', 'tested');
        $c->set(QueryInterface::class, new Query());
        $repo = new Repo($c);
        
        $this->assertEquals('tested', $repo->getRepository('testing'));
        $query = $repo->getRepository('query')->query();
        $this->assertEquals('query', $query->getTable());
    }
}