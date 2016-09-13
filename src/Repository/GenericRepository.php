<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Entity\Entity;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;

class GenericRepository extends AbstractRepository
{
    /**
     * GenericRepository constructor.
     *
     * @param Repo   $repo
     * @param string $table
     * @param array  $primaryKeys
     * @param bool   $autoIncrement
     * @internal param null|string $entityClass
     */
    public function __construct($repo, $table, $primaryKeys = [], $autoIncrement = false)
    {
        $this->repo        = $repo;
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys ?: ["{$table}_id"];
        $this->useAutoInsertId = $autoIncrement;
        $this->query       = $repo->getQuery();
        $this->now         = $repo->getCurrentDateTime();
    }
}