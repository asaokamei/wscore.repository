<?php
namespace WScore\Repository\Repository;

use WScore\Repository\Assembly\Collection;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\RelationInterface;

/**
 * Interface RepositoryInterface
 * 
 * defines API of a repository, a data access object to a database table. 
 *
 * @package WScore\Repository\Repository
 */
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
     * returns an entity for the primary keys, $keys.
     * throws an InvalidArgumentException if no or more than one entity were found.
     *
     * @param array $keys
     * @return EntityInterface
     * @throws \InvalidArgumentException
     */
    public function findByKey(array $keys);

    /**
     * returns an entity for the primary key value, $id.
     * works only for tables having only one primary key.
     * throws an InvalidArgumentException if no or more than one entity were found.
     *
     * @param string $id
     * @return EntityInterface
     * @throws \InvalidArgumentException
     */
    public function findById($id);

    /**
     * returns a new empty Collection object for this repository.
     *
     * @param EntityInterface[] $entities
     * @param null|RelationInterface $relation
     * @return Collection
     */
    public function newCollection(array $entities = [], $relation = null);

    /**
     * executes an SQL statement and returns a collection. 
     * 
     * @param string $sql
     * @param array  $data
     * @return Collection
     */
    public function collect($sql, array $data = []);

    /**
     * returns a Collection for given $keys as condition.
     *
     * @param array $keys
     * @return Collection
     */
    public function collectFor(array $keys);

    /**
     * returns a Collection for given primary Key.
     * throws an InvalidArgumentException if no or more than one entity were found.
     *
     * @param array $keys
     * @return Collection
     */
    public function collectByKey(array $keys);

    /**
     * returns a Collection for the primary key value, $id.
     * works only for tables having only one primary key.
     * throws an InvalidArgumentException if no or more than one entity were found.
     *
     * @param string $id
     * @return Collection
     * @throws \InvalidArgumentException
     */
    public function collectById($id);

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
     * apply scope$Name method in the repository to alter the query object. 
     * this method should return a new repository object so that original 
     * repository would not affected by the scope. 
     * 
     * @param string $name
     * @param array ...$args
     */
    public function scope($name, ...$args);

    /**
     * sets fetch mode for a PDOStatement after query.
     * override this method to use unsupported fetch mode.
     *
     * @Override
     * @param \PDOStatement $stmt
     */
    public function applyFetchMode(\PDOStatement $stmt);
}