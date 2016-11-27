<?php
namespace tests\Cases\SimpleId\Models;

use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Posts extends AbstractRepository
{
    protected $table = 'posts';

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
     * @return Join
     */
    public function tags()
    {
        return $this->repo->join($this, 'tags', 'posts_tags', ['id' => 'post_id'], ['tag_id' => 'id']);
    }
    
    public function user()
    {
        return $this->repo->belongsTo($this, 'users', ['user_id' => 'id']);
    }
}