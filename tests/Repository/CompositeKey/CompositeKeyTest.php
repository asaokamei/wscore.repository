<?php
namespace tests\Repository\CompositeKey;

use Interop\Container\ContainerInterface;
use PDO;
use PHPUnit_Framework_TestCase;
use tests\Utils\Composite\Fee;
use tests\Utils\Composite\FixtureCompositeKey;
use tests\Utils\Composite\Member;
use tests\Utils\Composite\Order;
use tests\Utils\Container;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\BelongsTo;
use WScore\Repository\Repo;

class CompositeKeyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Repo
     */
    private $repo;

    function setup()
    {
        class_exists(Repo::class);
        class_exists(Container::class);
        class_exists(Member::class);
        class_exists(Fee::class);
        class_exists(Order::class);
        class_exists(HasMany::class);
        class_exists(BelongsTo::class);

        $c       = $this->getContainer();
        $fixture = $c->get(FixtureCompositeKey::class);
        $fixture->createTables();
        $fixture->insertData();
        $this->repo = $c->get(Repo::class);
    }

    /**
     * @return Container
     */
    function getContainer()
    {
        $c = new Container();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $c->set(FixtureCompositeKey::class, function (ContainerInterface $c) {
            return new FixtureCompositeKey($c->get(PDO::class));
        });
        $c->set('member', function (ContainerInterface $c) {
            return new Member($c->get(Repo::class));
        });
        $c->set('fee', function (ContainerInterface $c) {
            return new Fee($c->get(Repo::class));
        });
        $c->set('order', function (ContainerInterface $c) {
            return new Order($c->get(Repo::class));
        });
        $c->set(Repo::class, function (ContainerInterface $c) {
            return new Repo($c);
        });

        return $c;
    }

    /**
     * @test
     */
    function HasMany_returns_related_entities()
    {
        /** @var Member $members */
        $members = $this->repo->getRepository('member');
        $main    = $members->findByKey(['type' => 1, 'code' => 100]);
        $this->assertEquals('Main Member', $main->get('name'));

        $orders = $members->orders($main);
        $this->assertEquals(3, count($orders->collect()));
        $this->assertEquals(1, count($orders->collect(['fee_year' => 2015])));
        $this->assertEquals(2, count($orders->collect(['fee_year' => 2016])));

        $order2015 = $orders->collect(['fee_year' => 2015])[0];
        $this->assertEquals([
            'member_type' => '1',
            'member_code' => '100',
            'fee_year'    => '2015',
            'fee_code'    => 'MEMBER'
        ], $order2015->getKeys());
    }

    /**
     * @test
     */
    function hasOne_returns_related_entity()
    {
        /** @var Order $order */
        $order         = $this->repo->getRepository('order');
        $order_11_2015 = $order->findByKey([
            'member_type' => '1',
            'member_code' => '100',
            'fee_year'    => '2015',
            'fee_code'    => 'MEMBER'
        ]);
        $member11      = $order->member($order_11_2015)->collect()[0];
        $this->assertEquals(1, $member11->get('type'));
        $this->assertEquals(100, $member11->get('code'));
    }

    /**
     * @test
     */
    function hasJoin_returns_related_entities()
    {
        /** @var Member $members */
        $members = $this->repo->getRepository('member');
        $main    = $members->findByKey(['type' => 1, 'code' => 100]);
        $this->assertEquals('Main Member', $main->get('name'));

        $feeJoined = $members->fees($main);
        $fees      = $feeJoined->collect();
        $this->assertEquals(3, count($fees));
    }

    /**
     * @test
     */
    function hasJoin_remove_deletes_a_relation()
    {
        /** @var Member $members */
        $members = $this->repo->getRepository('member');
        $main    = $members->findByKey(['type' => 1, 'code' => 100]);

        // retrieve associated fees.
        $joinedFees = $members->fees($main);
        $fees       = $joinedFees->collect();
        $this->assertEquals(3, count($fees));

        // this is the fee to remove.
        $feeRemove = $fees[1];
        $joinedFees->delete($feeRemove);
        $fees2 = $joinedFees->collect();
        $this->assertEquals(2, count($fees2));
        // make sure the remaining 2 fees are not $feeRemove
        foreach($fees2 as $f) {
            $this->assertNotEquals($feeRemove->getKeys(), $f->getKeys());
        }
    }

    /**
     * @test
     */
    function hasJoin_relate_adds_a_new_entity()
    {
        /** @var Member $members */
        $members = $this->repo->getRepository('member');
        $subMem  = $members->findByKey(['type' => 2, 'code' => 100]);

        // retrieve associated fees.
        $joinedFees = $members->fees($subMem);
        $fees1       = $joinedFees->collect();
        $this->assertEquals(2, count($fees1));

        // fees to add...
        $feeToAdd = $this->repo->getRepository('fee')->findByKey(['year' => 2016, 'type' => 2, 'code' => 'SYSTEM']);
        $this->assertNotNull($feeToAdd);
        $joinedFees->relate($feeToAdd);

        // check if $feeToAdd is related
        $fees2       = $joinedFees->collect();
        $this->assertEquals(3, count($fees2));
        $containsFeeToAdd = function() use($feeToAdd, $fees2) {
            foreach($fees2 as $f) {
                if ($feeToAdd->getKeys() == $f->getKeys()) {
                    return true;
                }
            }
            return false;
        };
        $this->assertTrue($containsFeeToAdd());
    }

    /**
     * @test
     */
    function hasJoin_clean_removes_all_relations()
    {
        /** @var Member $members */
        $members = $this->repo->getRepository('member');
        $subMem  = $members->findByKey(['type' => 2, 'code' => 100]);

        // retrieve associated fees.
        $joinedFees = $members->fees($subMem);
        $fees1       = $joinedFees->collect();
        $this->assertEquals(2, count($fees1));

        $joinedFees->clear();
        $this->assertEmpty($joinedFees->collect());
    }
}