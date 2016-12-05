<?php
namespace tests\Cases\Composite\Models;

use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Fee extends AbstractRepository
{
    const MEMBER = 'MEMBER';
    const SYSTEM = 'SYSTEM';
    
    protected $table = 'fees';

    protected $primaryKeys = ['year', 'code', 'type'];

    /**
     * GenericRepository constructor.
     *
     * @param Repo   $repo
     */
    public function __construct($repo)
    {
        parent::__construct($repo);
    }
}