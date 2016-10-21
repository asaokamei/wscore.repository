<?php
namespace tests\Repository\Query;

use WScore\Repository\Query\SqlBuilder;

class SqlBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function make_select_sql()
    {
        $b = SqlBuilder::forge('table');
        $this->assertEquals('SELECT * FROM table', $b->makeSelect());
        
        $b = SqlBuilder::forge('table')->where(['key' => 'val']);
        $this->assertEquals('SELECT * FROM table WHERE key = :holder_1', $b->makeSelect());
        $this->assertEquals('val', $b->getBindData()['holder_1']);

        $b = SqlBuilder::forge('table')->orderBy('test', 'DIR');
        $this->assertEquals('SELECT * FROM table ORDER BY test DIR', $b->makeSelect());
    }

    /**
     * @test
     */
    function make_select_with_where_in()
    {
        $b = SqlBuilder::forge('table')->where(['key' => ['v1', 'v2']]);
        $this->assertEquals('SELECT * FROM table WHERE key IN ( :holder_1, :holder_2 )', $b->makeSelect());
        $this->assertEquals('v1', $b->getBindData()['holder_1']);
        $this->assertEquals('v2', $b->getBindData()['holder_2']);
    }

    /**
     * @test
     */
    function make_select_with_where_or()
    {
        $b = SqlBuilder::forge('table')->where([['k1' => 'v1', 'k2' => 'v2']]);
        $this->assertEquals('SELECT * FROM table WHERE ( k1 = :holder_1 OR k2 = :holder_2 )', $b->makeSelect());
        $this->assertEquals('v1', $b->getBindData()['holder_1']);
        $this->assertEquals('v2', $b->getBindData()['holder_2']);
    }

    /**
     * @test
     */
    function make_select_complex_where()
    {
        $b = SqlBuilder::forge('table')
            ->where([
                'status' => 'test',
                ['k1' => 'v1', 'k2' => 'v2'],
                'type' => ['t1', 't2'],
            ]);
        $this->assertEquals(
            'SELECT * FROM table WHERE status = :holder_1 AND ( k1 = :holder_2 OR k2 = :holder_3 ) AND type IN ( :holder_4, :holder_5 )', 
            $b->makeSelect());
        $this->assertEquals('test', $b->getBindData()['holder_1']);
        $this->assertEquals('v1', $b->getBindData()['holder_2']);
        $this->assertEquals('v2', $b->getBindData()['holder_3']);
        $this->assertEquals('t1', $b->getBindData()['holder_4']);
        $this->assertEquals('t2', $b->getBindData()['holder_5']);
    }

    /**
     * @test
     */
    function make_insert_sql()
    {
        $b = SqlBuilder::forge('table');
        $this->assertEquals(
            'INSERT INTO table (k1, k2) VALUES (:holder_1, :holder_2);', 
            $b->makeInsert(['k1' => 'v1', 'k2' => 'v2']));

    }

    /**
     * @test
     */
    function make_update_sql()
    {
        $b = SqlBuilder::forge('table');
        $this->assertEquals(
            'UPDATE table SET k1 = :holder_1, k2 = :holder_2;',
            $b->makeUpdate(['k1' => 'v1', 'k2' => 'v2']));
        $this->assertEquals('v1', $b->getBindData()['holder_1']);
        $this->assertEquals('v2', $b->getBindData()['holder_2']);
    }

    /**
     * @test
     */
    function make_delete_sql()
    {
        $b = SqlBuilder::forge('table')->where(['k' => 'v']);
        $this->assertEquals(
            'DELETE FROM table WHERE k = :holder_1;',
            $b->makeDelete());
        $this->assertEquals('v', $b->getBindData()['holder_1']);
    }

    /**
     * @test
     */
    function make_count_sql()
    {
        $b = SqlBuilder::forge('table')->where(['k' => 'v']);
        $this->assertEquals(
            'SELECT COUNT(*) AS count FROM table WHERE k = :holder_1',
            $b->makeCount());
        $this->assertEquals('v', $b->getBindData()['holder_1']);
    }
}
