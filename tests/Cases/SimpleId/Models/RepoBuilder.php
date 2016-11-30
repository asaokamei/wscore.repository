<?php
namespace tests\Cases\SimpleId\Models;

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
        $repo->set(Fixture::class, function (Repo $c) {
            return new Fixture($c->get(PDO::class));
        });
        $repo->set('users', function(Repo $c) {
            return new Users($c);
        });
        $repo->set('posts', function(Repo $c) {
            return new Posts($c);
        });
        $repo->set('tags', function(Repo $c) {
            return new Tags($c);
        });
        $repo->set('posts_tags', function(Repo $c) {
            return new PostsTags($c);
        });

        return $repo;
    }
}