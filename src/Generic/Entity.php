<?php
namespace WScore\Repository\Generic;

use WScore\Repository\Abstracts\AbstractEntity;

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