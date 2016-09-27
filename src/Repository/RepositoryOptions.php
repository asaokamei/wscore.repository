<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Entity\Entity;
use WScore\Repository\Entity\EntityInterface;

/**
 * Class RepositoryOptions
 * 
 * used to setup a generic repository. 
 * the member property must be the same as that of GenericRepository's, 
 * and public. 
 *
 * @package WScore\Repository\Repository
 */
class RepositoryOptions
{
    /**
     * @Override
     * @var string
     */
    public $table;

    /**
     * @Override
     * @var string[]
     */
    public $primaryKeys = [];

    /**
     * @Override
     * @var string[]
     */
    public $columnList = [];

    /**
     * @Override
     * @var string|EntityInterface
     */
    public $entityClass = Entity::class;

    /**
     * @Override
     * @var string[]
     */
    public $timeStamps = [
        'created_at' => null,
        'updated_at' => null,
    ];

    /**
     * @Override
     * @var string
     */
    public $timeStampFormat = 'Y-m-d H:i:s';

    /**
     * @Override
     * @var bool
     */
    public $useAutoInsertId = false;
}