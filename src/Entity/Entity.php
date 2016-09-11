<?php
namespace WScore\Repository\Entity;

class Entity extends AbstractEntity 
{
    /**
     * Entity constructor.
     *
     * @param array $primaryKeys
     * @param array $columnList
     */
    public function __construct(array $primaryKeys, array $columnList = [])
    {
        $this->primaryKeys = $primaryKeys;
        $this->columnList  = $columnList;
        $this->isFetchDone = true;
    }


}