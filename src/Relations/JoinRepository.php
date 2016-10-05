<?php
namespace WScore\Repository\Relations;

use DateTimeImmutable;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;
use WScore\Repository\Repository\RepositoryInterface;

class JoinRepository extends AbstractJoinRepository 
{
    /**
     * AbstractJoinRepo constructor.
     *
     * @param Repo                $repo
     * @param string              $table
     * @param RepositoryInterface $fromRepo
     * @param RepositoryInterface $toRepo
     * @param QueryInterface      $query
     * @param DateTimeImmutable   $now
     * @internal param Repo $repo
     */
    public function __construct($repo, $table, $fromRepo, $toRepo, $query = null, $now = null)
    {
        $this->table       = $table;
        $this->primaryKeys = [$this->table . '_id'];
        $this->query       = $query ?: $repo->getQuery();
        $this->now         = $now ?: $repo->getCurrentDateTime();

        $this->from_repo    = $fromRepo;
        $this->from_convert = $this->makeConversion($fromRepo);

        $this->to_repo  = $toRepo;
        $this->to_convert = $this->makeConversion($toRepo);
    }
}