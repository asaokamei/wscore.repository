<?php
namespace WScore\Repository;

interface JoinRepositoryInterface
{
    /**
     * @param array $data
     * @return JoinEntityInterface
     */
    public function create($data);

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function select($entity);

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function selectFrom($entity);

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function selectTo($entity);

    /**
     * @param EntityInterface $entity1
     * @param EntityInterface $entity2
     * @return bool|JoinEntityInterface
     */
    public function insert($entity1, $entity2);

    /**
     * @param EntityInterface      $entity1
     * @param EntityInterface|null $entity2
     * @return bool
     */
    public function delete($entity1, $entity2 = null);
}