<?php
namespace tests\Cases\SimpleId\Models;

use Interop\Container\ContainerInterface;
use PDO;
use tests\Utils\Container;
use WScore\Repository\Repo;

class Services
{
    /**
     * @return Container
     */
    public static function get()
    {
        $self = new self();
        return $self->getContainer();
    }
    
    /**
     * @return Container
     */
    private function getContainer()
    {
        $c = new Container();
        $c->set(PDO::class, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
        $c->set(Repo::class, function (ContainerInterface $c) {
            $repo = new Repo($c);
            return $repo;
        });
        $c->set(Fixture::class, function (ContainerInterface $c) {
            return new Fixture($c->get(PDO::class));
        });
        $c->set('users', function(ContainerInterface $c) {
            return new Users($c->get(Repo::class));
        });
        $c->set('posts', function(ContainerInterface $c) {
            return new Posts($c->get(Repo::class));
        });
        $c->set('tags', function(ContainerInterface $c) {
            return new Tags($c->get(Repo::class));
        });
        $c->set('posts_tags', function(ContainerInterface $c) {
            return new PostsTags($c->get(Repo::class));
        });

        return $c;
    }
}