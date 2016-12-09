<?php
namespace tests\Repository\CompositeKey;

use PDO;
use PHPUnit_Framework_TestCase;
use tests\Utils\Composite\FixtureCompositeKey;
use WScore\Repository\Repo;

class GenericRepoTest extends PHPUnit_Framework_TestCase 
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Repo
     */
    private $repo;

    public function setup()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $fixture = new FixtureCompositeKey($this->pdo);
        $fixture->createTables();
        $fixture->insertData();
        $this->repo = new Repo($this->pdo);
    }

    public function test0()
    {
        $fees = $this->repo->getRepository('fees', ['year', 'type', 'code']);
        $fee2016 = $fees->find(['year' => 2016]);
        $this->assertCount(4, $fee2016);
        foreach($fee2016 as $f) {
            $this->assertEquals(2016, $f->get('year'));
        }
    }

    /**
     * @test
     */
    public function findByKey_works_for_composite_keys()
    {
        $members = $this->repo->getRepository('members', ['type', 'code']);
        $main    = $members->findByKey(['type' => 1, 'code' => 100]);
        $this->assertEquals('Main Member', $main->get('name'));
    }

}