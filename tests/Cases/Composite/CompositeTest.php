<?php
namespace tests\Cases\Composite;

use tests\Cases\Composite\Models\Fixture;
use tests\Cases\Composite\Models\RepoBuilder;
use WScore\Repository\Repo;

class CompositeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Repo
     */
    private $repo;

    public function setup()
    {
        $this->repo = RepoBuilder::get();
        /** @var Fixture $fix */
        $fix = $this->repo->get(Fixture::class);
        $fix->createTables();
    }

    public function test0()
    {
        $this->assertEquals(true, 1);
    }
}