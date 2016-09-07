<?php

interface RepositoryInterface
{
    /**
     * @return string|EntityInterface
     */
    public function getEntityClass();

    /**
     * @return string[]
     */
    public function getKeyColumns();

    /**
     * @param string|array $keys
     * @return EntityInterface[]
     */
    public function find($keys);

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function insert(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function update(EntityInterface $entity);

    /**
     * @param EntityInterface $entity
     * @return mixed
     */
    public function delete(EntityInterface $entity);

    /**
     * @return DaoInterface
     */
    public function getDao();

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface|null
     */
    public function hasOne(EntityInterface $entity, RepositoryInterface $repo, $convert = []);

    /**
     * @param EntityInterface     $entity
     * @param RepositoryInterface $repo
     * @param array               $convert
     * @return EntityInterface[]
     */
    public function hasMany(EntityInterface $entity, RepositoryInterface $repo, $convert = []);

    /**
     * @param EntityInterface $entity
     * @param RepositoryInterface $repo
     * @param string|null $joinTable
     * @param array $convert1
     * @param array $convert2
     * @return EntityInterface[]
     */
    public function hasJoin(
        EntityInterface $entity,
        RepositoryInterface $repo,
        $joinTable = '',
        $convert1 = [],
        $convert2 = []
    );
}