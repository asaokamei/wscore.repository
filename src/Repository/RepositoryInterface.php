<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\JoinRelationInterface;
use WScore\Repository\Relations\RelationInterface;

interface RepositoryInterface
{
    /**
     * returns table name. 
     * 
     * @return string
     */
    public function getTable();

    /**
     * returns a class name for entity object.
     *
     * @return string|EntityInterface
     */
    public function getEntityClass();

    /**
     * returns a primary key column names in array.
     *
     * @return string[]
     */
    public function getKeyColumns();

    /**
     * returns all the columns in the table.
     *
     * @return string[]
     */
    public function getColumnList();

    /**
     * create a new entity object.
     * this entity will be inserted into database.
     *
     * @param array $data
     * @return EntityInterface
     */
    public function create(array $data);

    /**
     * create a new entity object as fetched from database.
     * this entity will update an existing table row.
     *
     * @param array $data
     * @return EntityInterface
     */
    public function createAsFetched(array $data);

    /**
     * returns an array of entities for conditions as in $keys.
     *
     * @param array $keys
     * @return EntityInterface[]
     */
    public function find(array $keys);

    /**
     * returns an entity for the primary key, $key.
     * throws an InvalidArgumentException if found more than one entity.
     *
     * @param array|string $keys
     * @return EntityInterface|null
     */
    public function findByKey($keys);

    /**
     * returns a new empty Collection object for this repository.
     *
     * @param EntityInterface[] $entities
     * @param null|RelationInterface|JoinRelationInterface $relation
     * @return Collection
     */
    public function newCollection($entities = [], $relation = null);

    /**
     * executes an SQL statement and returns a collection. 
     * 
     * @param string $sql
     * @param array  $data
     * @return Collection|EntityInterface[]
     */
    public function collect($sql, array $data = []);

    /**
     * returns a Collection for given $keys as condition.
     *
     * @param array $keys
     * @return Collection|EntityInterface
     */
    public function collectFor(array $keys);

    /**
     * returns a Collection for given primary Key.
     * throws an InvalidArgumentException if found more than one entity.
     *
     * @param array|string $keys
     * @return Collection|EntityInterface[]
     */
    public function collectByKey($keys);

    /**
     * saves an $entity.
     * updates if the $entity is fetched, or insert if not.
     *
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function save(EntityInterface $entity);
    
    /**
     * for auto-increment table, this method returns a new entity
     * with the new id.
     *
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function insert(EntityInterface $entity);

    /**
     * updates the database record using the primary keys of $entity.
     *
     * @param EntityInterface $entity
     * @return mixed
     */
    public function update(EntityInterface $entity);

    /**
     * deletes a table record using the primary keys of $entity.
     *
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity);

    /**
     * creates a new QueryInterface object with ready to query on
     * the repository's table, fetch mode, and default order.
     *
     * @return QueryInterface
     */
    public function query();
    
    /**
     * sets fetch mode for a PDOStatement after query.
     * override this method to use unsupported fetch mode.
     *
     * @Override
     * @param \PDOStatement $stmt
     */
    public function applyFetchMode(\PDOStatement $stmt);
}