<?php
namespace tests\Cases\Composite;

use tests\Cases\Composite\Models\Fee;
use tests\Cases\Composite\Models\Fixture;
use tests\Cases\Composite\Models\Member;
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
        $fix->insertData();
    }

    /**
     * @test
     */
    public function relate_by_join()
    {
        // create a new user. 
        $users    = $this->repo->getRepository(Member::class);
        $user1001 = $users->create([
            'type' => Member::TYPE_MAIN,
            'code' => 1001,
            'name' => 'All Fees'
        ]);
        $user1001->save();

        // retrieve fees for 2015 and 2016. 
        $fees     = $this->repo->getRepository(Fee::class);
        $feesMain = $fees->collectFor(['type' => Member::TYPE_MAIN]);
        // relate fees to the $user1001.
        foreach ($feesMain as $fee) {
            $this->assertEquals(Member::TYPE_MAIN, $fee->type);
            $user1001->getRelatedEntities('fees')->add($fee);
        }

        // retrieve the user1001 as member1001. 
        $member1001 = $users->findByKey([
            'type' => Member::TYPE_MAIN,
            'code' => 1001,
        ]);
        $memberFees = $member1001->getRelatedEntities('fees');
        $this->assertEquals($feesMain->count(), $memberFees->count());
        foreach ($memberFees as $fee) {
            $this->assertEquals(Member::TYPE_MAIN, $fee->type);
        }

        $this->assertEquals(2400, $memberFees->sum('amount'));
    }
}