<?php
namespace tests\Cases\SimpleId\Models;

use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Tags extends AbstractRepository
{
    protected $table = 'tags';

    protected $primaryKeys = ['id'];

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
     * @return Join
     */
    public function posts()
    {
        return $this->repo->join($this, 'posts', 'posts_tags', ['id' => 'tag_id'], ['post_id' => 'id']);
    }
}