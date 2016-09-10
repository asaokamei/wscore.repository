<?php
namespace WScore\Repository\Generic;

use WScore\Repository\Abstracts\AbstractRepository;
use WScore\Repository\QueryInterface;
use WScore\Repository\Repo;

class Repository extends AbstractRepository
{
    /**
     * @var Repo
     */
    private $repo;

    public function __construct($repo, $table, $primaryKeys = null, $entityClass = null)
    {
        $this->repo        = $repo;
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys ?: ["{$table}_id"];
        $this->entityClass = $entityClass ?: Entity::class;
        $this->query       = $this->repo->get(QueryInterface::class);
    }
}