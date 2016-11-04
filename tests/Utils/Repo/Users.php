<?php
namespace tests\Utils\Repo;

use WScore\Repository\Relations\HasMany;
use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Users extends AbstractRepository
{
    protected $table = 'users';

    protected $primaryKeys = ['users_id'];

    protected $useAutoInsertId = true;

    protected $timeStamps
        = [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

    /**
     * Users constructor.
     *
     * @param Repo $repo
     */
    public function __construct($repo)
    {
        $this->repo  = $repo;
        $this->query = $repo->getQuery();
        $this->now   = $repo->getCurrentDateTime();
    }

    /**
     * @return HasMany
     */
    public function posts()
    {
        return $this->repo->hasMany($this, 'posts');
    }
}