<?php
namespace tests\Utils;

use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Users extends AbstractRepository
{
    protected $table = 'users';

    protected $primaryKeys = ['users_id'];

    protected $useAutoInsertId = true;

    /**
     * Users constructor.
     *
     * @param Repo $repo
     * @param QueryInterface $query
     */
    public function __construct($repo, $query)
    {
        $this->repo = $repo;
        $this->query = $query;
    }
}