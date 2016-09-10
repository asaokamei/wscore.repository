<?php
namespace tests\Utils;

use WScore\Repository\Abstracts\AbstractRepository;

class Repository extends AbstractRepository
{
    public function __construct(
        $table,
        $primaryKeys,
        $columnList,
        $entityClass,
        $timeStamps,
        $timeStampFormat,
        $query = null
    ) {
        $this->table           = $table;
        $this->primaryKeys     = $primaryKeys;
        $this->columnList      = $columnList;
        $this->entityClass     = $entityClass;
        $this->timeStamps      = $timeStamps;
        $this->timeStampFormat = $timeStampFormat;
        $this->query           = $query ?: new Query();
    }
}