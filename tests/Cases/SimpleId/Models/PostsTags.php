<?php
namespace tests\Cases\SimpleId\Models;

use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class PostsTags extends AbstractRepository
{
    protected $table = 'posts_tags';

    protected $primaryKeys = ['post_id', 'tag_id'];

    /**
     * Users constructor.
     *
     * @param Repo $repo
     */
    public function __construct($repo)
    {
        parent::__construct($repo);
    }
}