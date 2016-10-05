<?php
namespace tests\Utils\Composite;

use WScore\Repository\Relations\AbstractJoinRepository;
use WScore\Repository\Repo;

class Member2Fee extends AbstractJoinRepository  
{
    protected $table = 'orders';

    protected $primaryKeys = ['member_type', 'member_code', 'fee_year', 'fee_code', ];

    /**
     * Member2Fee constructor.
     *
     * @param Repo $repo
     */    
    public function __construct(Repo $repo)
    {
        $this->query = $repo->getQuery();
        $this->from_repo = $repo->getRepository('member');
        $this->from_convert = [
            'type' => 'member_type',
            'code' => 'member_code',
        ];
        $this->to_repo   = $repo->getRepository('fee');
        $this->to_convert = [
            'year' => 'fee_year',
            'type' => 'member_type',
            'code' => 'fee_code',
        ];
    }
}