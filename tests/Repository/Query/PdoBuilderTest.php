<?php
namespace tests\Repository\Query;

use PDO;
use tests\Utils\Repo\Fixture;
use WScore\Repository\Query\PdoBuilder;

class PdoBuilderTest extends \PHPUnit_Framework_TestCase 
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
     * @param array $sql
     * @return PdoBuilder
     */
    function getBuilder(array $sql)
    {
        return new PdoBuilder($this->pdo, $sql);
    }
    
    /**
     * @test
     */
    function execSelect_gets_a_row_from_database_table()
    {
        $sql = $this->getBuilder([
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
        $sql = $this->getBuilder([
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
        $sql = $this->getBuilder([
            'table' => 'users',
        ]);
        $this->fix->createUsers();
        $this->fix->insertUsers(2);

        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-1', $data['name']);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-2', $data['name']);

        $sql = $this->getBuilder([
            'table' => 'users',
            'conditions' => ['users_id' => 2]
        ]);
        $sql->execUpdate([
            'name' => 'test-update'
        ]);

        $sql = $this->getBuilder([
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
        $sql = $this->getBuilder([
            'table' => 'users',
            'orderBy' => [ 
                ['users_id', 'DESC' ]
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
        $sql = $this->getBuilder([
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
        $sql = $this->getBuilder([
            'table' => 'users',
        ]);

        $this->assertEquals(2, $sql->execCount());

        $sql = $this->getBuilder([
            'table' => 'users',
            'conditions' => ['users_id' => '1'],
        ]);
        $sql->execDelete();

        $sql = $this->getBuilder([
            'table' => 'users',
        ]);
        $this->assertEquals(1, $sql->execCount());
        $stmt = $sql->execSelect();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('name-2', $data['name']);
    }
}