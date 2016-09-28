<?php
namespace tests\Repository\Entity;

use WScore\Repository\Entity\Entity;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $table
     * @param array  $ids
     * @return Entity
     */
    private function entity($table = 'test', $ids = ['test_id'])
    {
        return new Entity($table, $ids);
    }

    /**
     * @test
     */
    function getTable_returns_table_name()
    {
        $entity = $this->entity('tested');
        $this->assertEquals('tested', $entity->getTable());
    }

    /**
     * @test
     */
    function getKeyColumns_returns_primary_key_names()
    {
        $entity = $this->entity('test', ['tested_id']);
        $this->assertEquals(['tested_id'], $entity->getKeyColumns());
        $entity = $this->entity('test', []);
        $this->assertEquals([], $entity->getKeyColumns());
    }

    /**
     * @test
     */
    function getIdName_returns_primary_key_name_if_only_one_primary_key()
    {
        $entity = $this->entity('test', ['tested_id']);
        $this->assertEquals('tested_id', $entity->getIdName());
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage multiple keys set.
     */
    function getIdName_should_throw_exception_if_more_than_one_primary_key_exists()
    {
        $entity = $this->entity('test', ['tested_id', 'more_id']);
        $this->assertEquals('tested_id', $entity->getIdName());
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage keys not set.
     */
    function getIdName_should_throw_exception_if_no_primary_key_exists()
    {
        $entity = $this->entity('test', []);
        $this->assertEquals('tested_id', $entity->getIdName());
    }

    /**
     * @test
     */
    function getKeys_returns_primary_keys_and_values()
    {
        $entity = $this->entity('test', ['tested_id', 'more_id']);
        $entity->fill(['tested_id' => 'tested', 'more_id' => 'done', 'other' => 'data']);
        $this->assertEquals(['tested_id' => 'tested', 'more_id' => 'done'], $entity->getKeys());
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage cannot set properties.
     */
    function cannot_set_property_and_throws_exception()
    {
        $entity = $this->entity('test', ['tested_id', 'more_id']);
        $entity->bad_property = 'throw';
    }

    /**
     * @test
     */
    function set_property_if_created_without_constructor()
    {
        $reflection = new \ReflectionClass(Entity::class);
        $entity = $reflection->newInstanceWithoutConstructor();
        $entity->good_property = 'clean';
        $this->assertTrue(true); // worked!
    }

    /**
     * @test
     */
    function setPrimaryKeyOnCreatedEntity_sets_a_new_primaryKey_value()
    {
        $entity = $this->entity('test', ['tested_id']);
        $entity->setPrimaryKeyOnCreatedEntity('new-id');
        $this->assertEquals('new-id', $entity->getIdValue());
    }

    /**
     * @test
     */
    function toArray_returns_data_from_entity()
    {
        $entity = $this->entity('test', ['tested_id']);
        $data   = ['tested_id' => 'tested', 'more' => 'done', 'other' => 'data'];
        $entity->fill($data);
        $this->assertEquals($data, $entity->toArray());
    }

    /**
     * @test
     */
    function toArray_returns_only_modified_data()
    {
        // create an entity that is fetched from a database.

        // instance without running a constructor.
        $reflection = new \ReflectionClass(Entity::class);
        /** @var Entity $entity */
        $entity = $reflection->newInstanceWithoutConstructor();

        // set properties; which will set isFetched to true.
        $this->assertFalse($entity->isFetched());
        /** @noinspection PhpUndefinedFieldInspection */
        $entity->test_id = 'tested';
        /** @noinspection PhpUndefinedFieldInspection */
        $entity->name    = 'name';
        $this->assertTrue($entity->isFetched());

        // execute the constructor.
        $refCtor         = $reflection->getConstructor();
        $refCtor->invoke($entity, 'tested', ['test_id']);
        $this->assertEquals([], $entity->getUpdatedData());
        $this->assertEquals(['test_id' => 'tested', 'name' => 'name'], $entity->toArray());

        // update some values
        $data = ['name' => 'new-name'];
        $entity->fill($data);
        $this->assertEquals($data, $entity->getUpdatedData());
        $this->assertEquals(['test_id' => 'tested', 'name' => 'new-name'], $entity->toArray());
    }

    /**
     * @test
     */
    function valueObject()
    {
        $entity = $this->entity('test', ['tested_id']);
        $propVo = new \ReflectionProperty($entity, 'valueObjectClasses');
        $propVo->setAccessible(true);
        $propVo->setValue($entity, [
            'date' => 'DateTimeImmutable',
            'more' => function($v) {return $v . '-done';},
        ]);
        $today  = '2016-09-26';
        $data   = ['date' => $today, 'more' => 'test'];
        $entity->fill($data);
        $this->assertEquals(new \DateTimeImmutable($today), $entity->get('date'));
        $this->assertEquals('test-done', $entity->get('more'));
    }
}