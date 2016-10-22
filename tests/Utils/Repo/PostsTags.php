<?php
namespace tests\Utils\Repo;

use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class PostsTags extends AbstractRepository
{
    protected $table = 'posts_tags';

    protected $primaryKeys = ['posts_tags_id'];

    protected $useAutoInsertId = true;

    protected $timeStamps
        = [
            'created_at' => 'created_at',
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
}