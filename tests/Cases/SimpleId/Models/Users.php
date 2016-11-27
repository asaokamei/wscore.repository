<?php
namespace tests\Cases\SimpleId\Models;

use WScore\Repository\Relations\HasMany;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Users extends AbstractRepository
{
    protected $table = 'users';

    protected $primaryKeys = ['id'];

    protected $useAutoInsertId = true;

    /**
     * Users constructor.
     *
     * @param Repo $repo
     */
    public function __construct($repo)
    {
        parent::__construct($repo);
    }

    /**
     * @return HasMany
     */
    public function posts()
    {
        return $this->repo->hasMany($this, 'posts', ['id' => 'user_id']);
    }
}