<?php
namespace tests\Repository;

use tests\Utils\Container;
use WScore\Repository\Generic\Entity;
use WScore\Repository\Generic\Repository;
use WScore\Repository\QueryInterface;
use WScore\Repository\Repo;

class RepoTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * @var Container
     */
    private $c;
    
    /**
     * @var Repo
     */
    private $repo;
    
    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        
        $this->c    = new Container();
        $this->c->set(QueryInterface::class, 'q');
        $this->repo = new Repo($this->c);
    }

    /**
     * @test
     */
    function repo_returns_generic_repository()
    {
        $dao = $this->repo->get('testing');
        $this->assertEquals(Repository::class, get_class($dao));
        $this->assertEquals('testing', $dao->getTable());
        $this->assertEquals(['testing_id'], $dao->getKeyColumns());
    }

    /**
     * @test
     */
    function entity_has_same_primary_keys_as_generic_repository()
    {
        $dao = $this->repo->get('testing');
        $this->assertEquals(Entity::class, $dao->getEntityClass());
        $entity = $dao->create([]);
        $this->assertEquals(Entity::class, get_class($entity));
        $this->assertEquals($dao->getKeyColumns(), $entity->getPrimaryKeyColumns());
    }
}