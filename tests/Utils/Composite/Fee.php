<?php
namespace tests\Utils\Composite;

use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Fee extends AbstractRepository
{
    protected $table = 'fees';

    protected $primaryKeys = ['year', 'code', 'type'];

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
}