<?php
namespace tests\Utils\Composite;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\HasOne;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Order extends AbstractRepository
{
    protected $table = 'orders';

    protected $primaryKeys = ['member_type', 'member_code', 'fee_year', 'fee_code', ];

    /**
     * GenericRepository constructor.
     *
     * @param Repo   $repo
     */
    public function __construct($repo)
    {
        $this->repo            = $repo;
        $this->query           = $repo->getQuery();
        $this->now             = $repo->getCurrentDateTime();
    }

    /**
     * @param EntityInterface $order_11_2015
     * @return HasOne
     */
    public function member($order_11_2015)
    {
        return $this->repo->hasOne($this, 'member', [
            'member_type' => 'type',
            'member_code' => 'code',
        ])->withEntity($order_11_2015);
    }
}