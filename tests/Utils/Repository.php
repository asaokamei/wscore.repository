<?php
namespace tests\Utils;

use WScore\Repository\Repository\AbstractRepository;

class Repository extends AbstractRepository
{
    public function __construct(
        $table,
        $primaryKeys,
        $columnList,
        $entityClass,
        $timeStamps,
        $timeStampFormat,
        $query = null,
        $now = ''
    ) {
        $this->table           = $table;
        $this->primaryKeys     = $primaryKeys;
        $this->columnList      = $columnList;
        $this->entityClass     = $entityClass;
        $this->timeStamps      = $timeStamps;
        $this->timeStampFormat = $timeStampFormat;
        $this->query           = $query ?: new Query();
        $this->now             = $now;
    }
}