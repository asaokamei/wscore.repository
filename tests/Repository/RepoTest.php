<?php
namespace tests\Repository;

use PDO;
use tests\Fixture;
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

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Fixture
     */
    private $fix;

    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(Repository::class);
        class_exists(Query::class);
        
        $this->pdo  = new PDO('sqlite::memory:');
        $this->fix  = new Fixture($this->pdo);
        $this->repo = new Repo(null, $this->pdo);
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
        $this->assertEquals($dao->getKeyColumns(), $entity->getKeyColumns());
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
    
    function test0()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $users = $this->repo->getRepository('users');
        
        $user1 = $users->findByKey(1);
        $this->assertEquals(['users_id' => 1], $user1->getKeys());
        $this->assertEquals('name-1', $user1->get('name'));
        
        $user2 = $users->find(['name' => 'name-2'])[0];
        $this->assertEquals(['users_id' => 2], $user2->getKeys());
        $this->assertEquals('name-2', $user2->get('name'));
    }
}