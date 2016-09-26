<?php
namespace tests\Utils\Composite;

use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Relations\HasMany;
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
        $this->repo            = $repo;
        $this->query           = $repo->getQuery();
        $this->now             = $repo->getCurrentDateTime();
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
     * @return \WScore\Repository\Relations\JoinBy
     */
    public function fees($member)
    {
        return $this->repo->joinBy($this, 'fees', 'member2fee')->withEntity($member);
    }
}