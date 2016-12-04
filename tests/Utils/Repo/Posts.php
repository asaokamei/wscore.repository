<?php
namespace tests\Utils\Repo;

use WScore\Repository\Relations\Join;
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Posts extends AbstractRepository
{
    protected $table = 'posts';

    protected $primaryKeys = ['post_id'];

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
        parent::__construct($repo);
    }
    
    /**
     * @return Join
     */
    public function tags()
    {
        return $this->repo->join($this, 'tags', 'posts_tags');
    }
}