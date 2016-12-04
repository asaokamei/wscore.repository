<?php
namespace tests\Cases\Composite\Models;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Member extends AbstractRepository
{
    protected $table = 'members';

    protected $primaryKeys = ['type', 'code'];

    /**
     * GenericRepository constructor.
     *
     * @param Repo   $repo
     */
    public function __construct($repo)
    {
        parent::__construct($repo);
    }

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->repo->hasMany($this, Order::class, ['type' => 'member_type', 'code' => 'member_code']);
    }

    /**
     * @param int|null $year
     * @return Join
     */
    public function fees($year = null)
    {
        $join = $this->repo->join($this, Fee::class, Order::class, [
            'type' => 'member_type',
            'code' => 'member_code',
        ], [
           'fee_year' => 'year',
           'member_type' => 'type',
           'fee_code' => 'code',
        ]);
        if ($year) {
            $join->setCondition(['year' => (int) $year]);
        }
        
        return $join;
    }
}