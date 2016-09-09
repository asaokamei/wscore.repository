<?php
namespace WScore\Repository\Abstracts;

use InvalidArgumentException;
use WScore\Repository\EntityInterface;
use WScore\Repository\Helpers\HelperMethods;
use WScore\Repository\JoinEntityInterface;
use WScore\Repository\JoinRepositoryInterface;
use WScore\Repository\QueryInterface;
use WScore\Repository\RepositoryInterface;

class AbstractJoinRepo implements JoinRepositoryInterface
{
    /**
     * @var QueryInterface
     */
    private $dao;

    /**
     * @var array
     */
    private $primaryKeys = [];

    /**
     * @var string|JoinEntityInterface
     */
    private $entity_class;

    /**
     * @var string|RepositoryInterface
     */
    private $from_repo;

    private $from_convert = [];

    /**
     * @var string|RepositoryInterface
     */
    private $to_repo;

    private $to_convert = [];

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function select($entity)
    {
        $class = $this->from_repo->getEntityClass();
        if ($entity instanceof $class) {
            return $this->selectFrom($entity);
        }
        $class = $this->to_repo->getEntityClass();
        if ($entity instanceof $class) {
            return $this->selectTo($entity);
        }
        throw new InvalidArgumentException('entity class is not either from or to.');
    }

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function selectFrom($entity)
    {
        $keys = $this->makeKeys($entity);
        $stmt = $this->dao->select($keys);

        return $stmt->fetchObject($this->entity_class);
    }

    /**
     * @param EntityInterface $entity
     * @return JoinEntityInterface[]
     */
    public function selectTo($entity)
    {
        $keys = $this->makeKeys(null, $entity);
        $stmt = $this->dao->select($keys);

        return $stmt->fetchObject($this->entity_class);
    }

    /**
     * @param EntityInterface $entity1
     * @param EntityInterface $entity2
     * @return bool|JoinEntityInterface
     */
    public function insert($entity1, $entity2)
    {
        $keys1 = HelperMethods::convertDataKeys($entity1->getKeys(), $this->from_convert);
        $keys2 = HelperMethods::convertDataKeys($entity2->getKeys(), $this->to_convert);
        $data  = array_merge($keys1, $keys2);

        if (!$id = $this->dao->insert($data)) {
            return null; // failed to insert...
        }
        $class = $this->entity_class;
        if ($id !== true) {
            // returned some id value. add the primary key to the data.
            $data[$this->primaryKeys[0]] = $id;
        }
        return $class::create($data);
    }

    /**
     * @param EntityInterface      $entity1
     * @param EntityInterface|null $entity2
     * @return bool
     */
    public function delete($entity1, $entity2 = null)
    {
        $keys = $this->makeKeys($entity1, $entity2);
        return $this->dao->delete($keys);
    }

    /**
     * @param EntityInterface      $entity_from
     * @param EntityInterface|null $entity_to
     * @return array
     */
    private function makeKeys($entity_from = null, $entity_to = null)
    {
        $keys = [];
        if ($entity_from) {
            $keys = array_merge(
                $keys,
                HelperMethods::convertDataKeys($entity_from->getKeys(), $this->from_convert)
            );
        }
        if ($entity_to) {
            $keys = array_merge(
                $keys,
                HelperMethods::convertDataKeys($entity_to->getKeys(), $this->to_convert)
            );
        }
        return $keys;
    }
}