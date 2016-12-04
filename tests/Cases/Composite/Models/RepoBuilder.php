<?php
namespace tests\Cases\Composite\Models;

use PDO;
use WScore\Repository\Repo;

class RepoBuilder
{
    /**
     * @return Repo
     */
    public static function get()
    {
        $self = new self();

        return $self->getContainer();
    }

    /**
     * @return Repo
     */
    private function getContainer()
    {
        $repo = new Repo();
        $repo->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $repo->set(Fixture::class, function (Repo $repo) {
            return new Fixture($repo->get(PDO::class));
        });
        $repo->set(Member::class, function(Repo $repo) {
            return new Member($repo);
        });
        $repo->set(Order::class, function(Repo $repo) {
            return new Order($repo);
        });
        $repo->set(Fee::class, function(Repo $repo) {
            return new Fee($repo);
        });
        
        return $repo;
    }

}