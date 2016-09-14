WScore/Repository
=================

Yet-Another database repository for PHP. 
It is

* similar to Active Record pattern with separation of concerns,
* easy to use, simple to understand.
* ready for complex primary keys.

Under development. Not ready for production. 

### Separation

There are three layers. 

```
  EntityInterface
       ↑
  RepositoryInterface/RelationInterface
       ↓
  QueryInterface
```

* The top layer is the `EntityInterface`, which is 
independent from the bottom layers. One of the aim of 
this repository is to reduce the background code of 
entity classes. 

* The `RepositoryInterface` layer persists the entities 
using `QueryInterface` layer, while `RelationInterface` relates
entities using repositories. 

* The `QueryInterface` layer is at the bottom of the layers 
responsible of querying database. 

* There is a `Repo` class which is a container as well as 
a factory for repositories and relations. 
Requires a container that implements `ContainerInterface`. 

Sample Code
===========

Assume, `users` repository is already set up in `$repo`. 

To create an entity and save it. 

```php
$users = $repo->getRepository('users');
$user1 = $users->create(['name' => 'my name']);
$id    = $users->save($user1); // should return inserted id.
```

To retrieve an entity, modify it, and save it.

```php
$users = $repo->getRepository('users');
$user1 = $users->findByKey(1);
$user1->fill(['name' => 'your name']);
$users->save($user1);
```

Relate to another table, `posts`, as `hasMany` relation.

```php
$user1      = $repo->getRepository('users')->findByKey(1);
$testPost   = $repo->getRepository('posts')->find(['author' => 'Test'])
$user1posts = $repo->hasMany('users', 'posts', $user1);

$user1posts->relate($testPost);
echo $user1post->count(); // maybe 1?
$posts = $user1posts->find(); // 
```



Preparing WScore/Repository.
======

Repo and Container
----

`Repo` is a repository manager, container, and a factory. 
Use another container that is `ContainerInterface` compatible. 

```php
$c = new Container();
$c->set(PDO::class, function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});
$c->set(QueryInterface::class, function(ContainerInterface $c) {
    new PdoQuery($c->get(PDO::class));
});
$c->set(Repo::class, function(ContainerInterface $c) {
    return new Repo($c);
});
```

Repository and Entity
----

A generic repository. 

```php
$repo  = $c->get(Repo::class);
$users = $repo->getRepository('users', ['user_id'], true);
$user1 = $users->create(['name' => 'my name']);
$users->save($user1);
```


To use a class as a repository, prepare the class and set 
factory in the container. 

Create a class. 

```php
class Posts {
    protected $repo; // Repo
    protected $table = 'posts';
    protected $primaryKeys = ['post_id'];
    protected $useAutoInsertId = true;
    protected $timeStamps = [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    /**
     * @return HasOne
     */
    public function user(EntityInterface $post) {
        return $repo->hasOne($this, 'users', $post);
    }
}
```

Set up a container for the repository class and factory for it. 

```php
$container->set(
    Posts::class, function($c) {
        return new Posts($c->get(Repo::class));
    }
);
$repo->getRepository(Posts::class);
```

More 
====

maybe implement Active Record pattern?

