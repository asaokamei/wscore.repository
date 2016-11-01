<?php
namespace tests\Repository;

use tests\Utils\Query;
use tests\Utils\Repository;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Query\QueryInterface;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Repository
     */
    public $repo;

    /**
     * @var Query
     */
    public $q;

    function setup()
    {
        $this->q    = new Query();
        $this->repo = new Repository(
            'testTable',
            ['p1', 'p2'],
            ['col1', 'col2'],
            Entity::class,
            ['updated_at' => 'test_update', 'created_at' => 'test_create'],
            'Y/m/d H:i',
            $this->q
        );

    }

    function test0()
    {
        $repo = $this->repo;
        $this->assertEquals('testTable', $repo->getTable());
        $this->assertEquals(['p1', 'p2'], $repo->getKeyColumns());
        $this->assertEquals(['col1', 'col2'], $repo->getColumnList());
        $this->assertEquals(Entity::class, $repo->getEntityClass());
    }

    /**
     * @test
     */
    function query_returns_QueryInterface_instance()
    {
        $query = $this->repo->query();
        $this->assertEquals(true, $query instanceof QueryInterface);
    }

    /**
     * @test
     */
    function repository_passes_info_to_entity()
    {
        $repo = new Repository(
            'testTable',
            ['p1', 'p2'],
            ['p1', 'p2', 'col1', 'col2'],
            Entity::class,
            ['updated_at' => 'mod-date'],
            'Y/m/d H:i',
            $this->q,
            new \DateTimeImmutable('2016/09/27 16:00')
        );
        $entity = $repo->create(['p1' => 'v1', 'p2' => 'v2', 'col1' => 'val', 'col2' => 'test', 'bad' => 'error']);
        $this->assertEquals(['p1' => 'v1', 'p2' => 'v2', 'col1' => 'val', 'col2' => 'test', 'bad' => 'error'], $entity->toArray());
        $this->assertEquals(['p1' => 'v1', 'p2' => 'v2'], $entity->getKeys());
        $this->assertEquals(['p1', 'p2'], $entity->getKeyColumns());
        $repo->insert($entity);
        $this->assertEquals(['p1' => 'v1', 'p2' => 'v2', 'col1' => 'val', 'col2' => 'test', 'mod-date' => '2016/09/27 16:00'], $this->q->data);
    }

    /**
     * @test
     */
    function find_and_findByKey_queries_by_keys()
    {
        $this->repo->find(['key' => 'tested']);
        $this->assertEquals(['key' => 'tested'], $this->q->keys);

        $this->repo->findByKey(['key' => 'tested']);
        $this->assertEquals(['key' => 'tested'], $this->q->keys);
    }

    /**
     * @test
     */
    function findByKey_accepts_simple_value()
    {
        $repo = new Repository(
            'testTable',
            ['p1',],
            ['col1', 'col2'],
            'testEntity',
            ['updated_at' => 'test_update'],
            'Y/m/d H:i',
            $this->q
        );

        $repo->findByKey('p-value');
        $this->assertEquals(['p1' => 'p-value'], $this->q->keys);
    }

    /**
     * @test
     */
    function create_method_creates_a_new_entity()
    {
        $entity = $this->repo->create(['col1' => 'val', 'col2' => 'test', ]);
        $this->repo->insert($entity);
        $this->assertEquals('val', $this->q->data['col1']);
        $this->assertEquals('test', $this->q->data['col2']);
        $this->assertArrayHasKey('test_create', $this->q->data);
        $this->assertArrayHasKey('test_update', $this->q->data);
    }

    /**
     * @test
     */
    function update_filters_columns_not_in_getColumns()
    {
        $repo = new Repository(
            'testTable',
            ['p1', 'p2'],
            ['p1', 'p2', 'col1', 'col2'],
            Entity::class,
            ['updated_at' => 'test_update'],
            'Y/m/d H:i',
            $this->q
        );
        $entity = $repo->create(['col1' => 'val', 'col2' => 'test', 'p1' => 'v1', 'p2' => 'v2']);
        $this->assertEquals(['col1' => 'val', 'col2' => 'test', 'p1' => 'v1', 'p2' => 'v2'], $entity->toArray());
        $repo->update($entity);

        $this->assertEquals(['p1' => 'v1', 'p2' => 'v2'], $this->q->keys);
        $this->assertEquals('val', $this->q->data['col1']);
        $this->assertEquals('test', $this->q->data['col2']);
        $this->assertArrayHasKey('test_update', $this->q->data);
    }

    /**
     * @test
     */
    function deletes_passes_primaryKeys_to_Query_instance()
    {
        $repo = new Repository(
            'testTable',
            ['p1', 'p2'],
            ['p1', 'p2', 'col1', 'col2'],
            Entity::class,
            ['updated_at' => 'test_update'],
            'Y/m/d H:i',
            $this->q
        );
        $entity = $repo->create(['col1' => 'val', 'col2' => 'test', 'p1' => 'v1', 'p2' => 'v2']);
        $this->assertEquals(['col1' => 'val', 'col2' => 'test', 'p1' => 'v1', 'p2' => 'v2'], $entity->toArray());
        $repo->delete($entity);

        $this->assertEquals(['p1' => 'v1', 'p2' => 'v2'], $this->q->keys);
        $this->assertEquals(null, $this->q->data);
    }
}