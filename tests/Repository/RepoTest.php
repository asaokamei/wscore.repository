<?php
namespace tests\Repository;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Fixture;
use tests\Utils\Container;
use tests\Utils\Query;
use tests\Utils\Users;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Query\PdoQuery;
use WScore\Repository\Repository\GenericRepository;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;

class RepoTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * @var Repo
     */
    private $repo;

    /**
     * @var Fixture
     */
    private $fix;

    function setup()
    {
        class_exists(Container::class);
        class_exists(Repo::class);
        class_exists(GenericRepository::class);
        class_exists(Query::class);

        $c    = new Container();
        $c->set(PDO::class, function () {
            return new PDO('sqlite::memory:');
        });
        $c->set(Fixture::class, function (ContainerInterface $c) {
            return new Fixture($c->get(PDO::class));
        });
        $c->set(QueryInterface::class, function(ContainerInterface $c) {
            return new PdoQuery($c->get(PDO::class));
        });
        $c->set('users', function(ContainerInterface $c ) {
            new Users($c->get(Repo::class), $c->get(QueryInterface::class));
        });
        $c->set(Repo::class, function(ContainerInterface $c) {
            return new Repo($c);
        });
        $this->repo = $c->get(Repo::class);
        $this->fix  = $c->get(Fixture::class);

    }

    /**
     * @test
     */
    function repo_returns_generic_repository()
    {
        $dao = $this->repo->getRepository('testing');
        $this->assertEquals(GenericRepository::class, get_class($dao));
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
    function Repo_uses_container_to_retrieve_various_objects()
    {
        $c    = new Container();
        $c->set('testing', 'tested');
        $c->set(QueryInterface::class, new PdoQuery(null));
        $c->set(CurrentDateTime::class, 'test-now');
        $repo = new Repo($c);

        // retrieve repository, 'tested'.
        $this->assertEquals('tested', $repo->getRepository('testing'));

        // retrieve QueryInterface
        $query = $repo->getQuery();
        $this->assertTrue($query instanceof QueryInterface);

        // retrieve query-table repository, which has query object with table 'query-table'
        $query = $repo->getRepository('query-table')->query();
        $this->assertEquals('query-table', $query->getTable());

        // retrieve CurrentDateTime
        $this->assertEquals('test-now', $repo->getCurrentDateTime());
    }

    /**
     * @test
     */
    function Repo_retrieves_entities_from_database()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $users = $this->repo->getRepository('users');
        
        $user1 = $users->findByKey(1);
        $this->assertEquals(1, $user1->getIdValue());
        $this->assertEquals('name-1', $user1->get('name'));
        
        $user2 = $users->find(['name' => 'name-2'])[0];
        $this->assertEquals(2, $user2->getIdValue());
        $this->assertEquals('name-2', $user2->get('name'));
    }

    /**
     * @test
     */
    function Repo_inserts_entity_and_sets_auto_increment_key()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(1);
        $users = $this->repo->getRepository('users');
        // magic: make repository auto-inserted-id aware!
        $auto = new \ReflectionProperty($users, 'useAutoInsertId');
        $auto->setAccessible(true);
        $auto->setValue($users, true);

        $this->assertEquals(null, $users->findByKey(2));
        $userN = $users->create([
            'name' => 'test-insert',
            'gender' => 'T',
                       ]);
        $users->insert($userN);

        $user2 = $users->findByKey(2);
        $this->assertEquals($userN->getKeys(), $user2->getKeys());
        $this->assertEquals($userN->get('name'), $user2->get('name'));
    }

    /**
     * @test
     */
    function Repo_updates_only_the_entity_data()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $users = $this->repo->getRepository('users');

        $user2 = $users->findByKey(2);
        $user2->fill(['name' => 'test-update']);
        $users->update($user2);

        $user1 = $users->findByKey(1);
        $this->assertEquals(1, $user1->getIdValue());
        $this->assertEquals('name-1', $user1->get('name'));

        $user2 = $users->findByKey(2);
        $this->assertEquals(2, $user2->getIdValue());
        $this->assertEquals('test-update', $user2->get('name'));
    }

    /**
     * @test
     */
    function Repo_save_insert_or_update_depending_on_entity_is_fetched()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(1);
        $users = $this->repo->getRepository('users');

        $user1 = $users->findByKey(1);
        $user1->fill(['name' => 'test-update']);

        $user2 = $users->create([
            'name' => 'test-insert',
            'gender' => 'T',
                                ]);

        $users->save($user1);
        $users->save($user2);

        $this->assertEquals('test-update', $users->findByKey(1)->get('name'));
        $this->assertEquals('test-insert', $users->findByKey(2)->get('name'));
    }

    /**
     * @test
     */
    function Repo_deletes_entity()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(3);
        $users = $this->repo->getRepository('users');

        $user2 = $users->findByKey(2);
        $users->delete($user2);

        $this->assertEquals(null, $users->findByKey(2));
        $this->assertEquals(1, $users->findByKey(1)->getIdValue());
        $this->assertEquals(3, $users->findByKey(3)->getIdValue());
    }
}