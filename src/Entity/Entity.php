<?php
namespace WScore\Repository\Entity;

use WScore\Repository\Repository\RepositoryInterface;

class Entity extends AbstractEntity
{
    /**
     * Entity constructor.
     * give $repository as 3rd parameter to enable ActiveRecord
     * methods (save, getRelation).
     *
     * @param string $table
     * @param array  $primaryKeys
     * @param RepositoryInterface  $repo
     */
    public function __construct($table, array $primaryKeys, $repo = null)
    {
        $this->repo = $repo;
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys;
        $this->_setFetchDone();
    }
}