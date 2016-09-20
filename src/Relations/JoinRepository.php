<?php
namespace WScore\Repository\Relations;

use InvalidArgumentException;
use WScore\Repository\Entity\Entity;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Helpers\CurrentDateTime;
use WScore\Repository\Helpers\HelperMethods;
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
     */
    public function __construct($repo, $table, $fromRepo, $toRepo)
    {
        $this->table       = $table;
        $this->primaryKeys = [$this->table . '_id'];
        $this->query       = $repo->getQuery();
        $this->now         = $repo->getCurrentDateTime();

        $tabs = [$fromRepo->getTable(), $toRepo->getTable()];
        $repo = [$fromRepo->getTable() => $fromRepo, $toRepo->getTable() => $toRepo];
        sort($tabs);
        ksort($repo);
        $this->from_table = $tabs[0];
        $this->from_repo  = $repo[$this->from_table];
        foreach ($this->from_repo->getKeyColumns() as $key) {
            $this->from_convert[$key] = $this->from_table . '_' . $key;
        }
        $this->to_table = $tabs[1];
        $this->to_repo  = $repo[$this->to_table];
        foreach ($this->to_repo->getKeyColumns() as $key) {
            $this->to_convert[$key] = $this->to_table . '_' . $key;
        }
    }
}