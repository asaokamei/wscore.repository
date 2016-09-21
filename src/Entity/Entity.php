<?php
namespace WScore\Repository\Entity;

class Entity extends AbstractEntity
{
    /**
     * Entity constructor.
     *
     * @param string $table
     * @param array $primaryKeys
     */
    public function __construct($table, array $primaryKeys)
    {
        $this->table       = $table;
        $this->primaryKeys = $primaryKeys;
        $this->isFetchDone = true;
    }


}