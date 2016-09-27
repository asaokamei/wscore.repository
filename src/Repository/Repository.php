<?php
namespace WScore\Repository\Repository;

use DateTimeImmutable;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Repo;

class Repository extends AbstractRepository
{
    /**
     * GenericRepository constructor.
     *
     * @param Repo                   $repo
     * @param QueryInterface         $query
     * @param DateTimeImmutable      $now
     * @param null|RepositoryOptions $options
     */
    public function __construct($repo, $query, $now, $options = null)
    {
        $this->repo  = $repo;
        $this->query = $query;
        $this->now   = $now;
        if ($options) {
            $this->_setupRepositoryOptions($options);
        }
    }

    /**
     * @param RepositoryOptions $options
     */
    private function _setupRepositoryOptions($options)
    {
        foreach ((array)$options as $key => $value) {
            $this->$key = $value;
        }
    }
}