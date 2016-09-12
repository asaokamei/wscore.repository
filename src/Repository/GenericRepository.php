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
     * @param Repo $repo
     * @param string $table
     * @param array $primaryKeys
     * @param string|null $entityClass
     */
    public function __construct($repo, $table, $primaryKeys = [], $entityClass = null)
    {
        $this->repo        = $repo;
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys ?: ["{$table}_id"];
        $this->entityClass = $entityClass ?: Entity::class;
        $this->query       = $this->repo->getQuery();
        $this->now         = $repo->getCurrentDateTime();
    }
}