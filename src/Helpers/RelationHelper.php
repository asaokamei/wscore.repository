<?php
namespace WScore\Repository\Helpers;

use PDOStatement;
use WScore\Repository\EntityInterface;
use WScore\Repository\JoinRepositoryInterface;
use WScore\Repository\RepositoryInterface;

class RelationHelper
{
    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface|null
     */
    public static function hasOne(EntityInterface $entity, RepositoryInterface $repo, $convert = [])
    {
        $targetKeys = $repo->getKeyColumns();
        $sourceData = $entity->toArray();
        $keys       = HelperMethods::filterDataByKeys($sourceData, $targetKeys);
        $keys       = HelperMethods::convertDataKeys($keys, $convert);
        $found = $repo->find($keys);
        if ($found) {
            return $found[0];
        }
        return null;
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface[]
     */
    public static function hasMany(EntityInterface $entity, RepositoryInterface $repo, $convert = [])
    {
        $keys = $entity->getKeys();
        $keys = HelperMethods::convertDataKeys($keys, $convert);
        return $repo->find($keys);
    }

    /**
     * create a join table name from 2 joined tables.
     * sort table name by alphabetical order.
     *
     * @param string $table1
     * @param string $table2
     * @return string
     */
    public static function makeJoinTableName($table1, $table2)
    {
        $list = [$table1, $table2];
        sort($list);
        return implode('_', $list);
    }

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param string              $joinTable
     * @param array               $convert1
     * @param array               $convert2
     * @return PDOStatement
     */
    public static function hasJoin(
        EntityInterface $entity,
        RepositoryInterface $repo,
        $joinTable,
        $convert1 = [],
        $convert2 = []
    ) {
        // create the join-table name if not given.
        return $repo->getDao()->join($joinTable, $entity->getKeys(), $convert1, $convert2);
    }

    /**
     * @param EntityInterface         $entity
     * @param JoinRepositoryInterface $join
     * @param RepositoryInterface     $repo
     * @return array
     */
    public static function hasJoinRepo(
        EntityInterface $entity,
        JoinRepositoryInterface $join,
        RepositoryInterface $repo
    ) {
        $found    = [];
        $joinList = $join->selectFrom($entity);
        foreach($joinList as $j) {
            $found[] = $repo->find($j->getKeysTo());
        }
        return $found;
    }
}