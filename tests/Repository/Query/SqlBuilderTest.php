<?php
namespace tests\Repository\Query;

use PDO;
use tests\Fixture;
use WScore\Repository\Query\SqlBuilder;

class SqlBuilderTest extends \PHPUnit_Framework_TestCase 
{
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
        $this->pdo = $pdo = new PDO('sqlite::memory:');
        $this->fix = new Fixture($pdo);
    }

    /**
     * @test
     */
    function execSelect_gets_a_row_from_database_table()
    {
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);
        $this->fix->createUsers();
        $this->fix->insertUsers(1);
        
        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-1', $data['name']);
    }

    /**
     * @test
     */
    function execInsert_inserts_data_into_database_table()
    {
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);
        $this->fix->createUsers();

        $sql->execInsert([
            'name' => 'test-insert',
            'gender' => 'T',
        ]);

        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('test-insert', $data['name']);
    }

    /**
     * @test
     */
    function execUpdate()
    {
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);
        $this->fix->createUsers();
        $this->fix->insertUsers(2);

        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-1', $data['name']);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-2', $data['name']);

        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
            'conditions' => ['user_id' => 2]
        ]);
        $sql->execUpdate([
            'name' => 'test-update'
        ]);

        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);
        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-1', $data['name']);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('test-update', $data['name']);
    }

    /**
     * @test
     */
    function orderBy_sets_order_of_select()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
            'orderBy' => [ 
                ['user_id', 'DESC' ]
            ],
        ]);
        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-2', $data['name']);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-1', $data['name']);
    }

    /**
     * @test
     */
    function execCount_returns_number_of_rows()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);

        $this->assertEquals(2, $sql->execCount());

        $sql->execInsert([
            'name' => 'test-insert',
            'gender' => 'T',
        ]);

        $this->assertEquals(3, $sql->execCount());
    }

    /**
     * @test
     */
    function execDelete()
    {
        $this->fix->createUsers();
        $this->fix->insertUsers(2);
        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);

        $this->assertEquals(2, $sql->execCount());

        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
            'conditions' => ['user_id' => '1'],
        ]);
        $sql->execDelete();

        $sql = new SqlBuilder($this->pdo, [
            'table' => 'users',
        ]);
        $this->assertEquals(1, $sql->execCount());
        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-2', $data['name']);
    }
}