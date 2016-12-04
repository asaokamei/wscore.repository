<?php
namespace tests\Utils\Composite;

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
     * @param EntityInterface $member
     * @return HasMany
     */
    public function orders($member)
    {
        return $this->repo->hasMany($this, 'order', ['type' => 'member_type', 'code' => 'member_code'])->withEntity($member);
    }

    /**
     * @param EntityInterface $member
     * @return Join
     */
    public function fees($member)
    {
        return $this->repo->join($this, 'fee', 'order', [
            'type' => 'member_type',
            'code' => 'member_code',
        ], [
           'fee_year' => 'year',
           'member_type' => 'type',
           'fee_code' => 'code',
        ])->withEntity($member);
    }
}