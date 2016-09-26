WScore/Repository
=================

Yet-Another ORM, or database layer library, for PHP. 
It is (probably) a Repository Pattern with implementation 
similar to Active Record with separated layers. That means 
most of the operation directly access and modifies database. 

Other than being able to read, create, update, delete, 
and relate entities, it is 

* easy to use, simple to understand, 
* ready for composite primary keys,
* possible to code like an Active Record. 

Under development. Not ready for production. 

Installation: `git clone https://github.com/asaokamei/wscore.repository`

### Separation of Layers

There are three layers. 

```
                EntityInterface
                 ↑         ↑
  RepositoryInterface ← RelationInterface
           ↓
  QueryInterface
```

* The top layer is the `EntityInterface`, which is 
independent from the bottom layers. One of the aim of 
this repository is to reduce the background code behind 
entity objects.  
* The `RepositoryInterface` layer is a gateway to database tables 
that persist the entities using `QueryInterface` object. 
The `relationInterface` relates entities using repositories. 
* The `QueryInterface` layer is at the bottom of the layers 
responsible of querying database. 

There is a `Repo` class which serves as a container as well as 
a factory for repositories and relations. 
Requires a container that implements `ContainerInterface`. 

There is `JoinRepositoryInterface` and `JoinRelationInterface`
for many-to-many (join) relation.

Sample Code
===========

Sample database. 

```sql
CREATE TABLE users (
    users_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL UNIQUE
    created_at  DATETIME
);

CREATE TABLE posts (
    post_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    users_id    INTEGER,
    contents    VARCHAR(256)
);
```

Create a Repository
----

Create a repository for a database table, such as. 

```php
<?php
use WScore\Repository\Repo;
use WScore\Repository\Entity\EntityInterface;
use WScore\Repository\Query\QueryInterface;
use WScore\Repository\Relations\HasMany;
use WScore\Repository\Repository\AbstractRepository;

class Users extends AbstractRepository
{
    protected $table = 'users';           // table name
    protected $primaryKeys = ['user_id']; // primary keys in array
    protected $useAutoInsertId = true;    // use auto-incremented ID.
    protected $columnList = [             // for mass-assignments
        'name',     
    ];
    protected $timeStamps = [             // if any...
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

    /**
     * @param Repo $repo
     * @param QueryInterface $query 
     */
    public function __construct(Repo $repo, QueryInterface $query = null) 
    {
        $this->repo  = $repo;
        $this->query = $query ?: $repo->getQuery();
        $this->now   = $repo->getCurrentDateTime();
    }
    
    /**
     * @param EntityInterface $user
     * @return HasMany
     */
    public function posts(EntityInterface $user) 
    {
        return $this->repo->hasMany($this, 'posts')->withEntity($user);
    }
}
```

The `AbstractRepository` serves as a convenient way to create 
a repository but any class can be served as repository which 
implements `WScore\Repository\Repository\RepositoryInterface`. 

Using ContainerInterface
----

Use another container that is `ContainerInterface` compatible. 

```php
$c = new Container();
$c->set(PDO::class, function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});
$c->set(Repo::class, function(ContainerInterface $c) {
    return new Repo($c);
});
$c->set('users', function(ContainerInterface $c) {
    new new Users($c->get(Repo::class), $c->get(QueryInterface::class));
});
```

`Repo` is a repository manager, container, and a factory. 

Repository and Entities
----

#### create and save

To create an entity and save it: 

```php
$users = $repo->getRepository('users');
$user1 = $users->create(['name' => 'my name']);
$id    = $users->save($user1); // should return inserted id.
```

#### retrieve, modify, and save. 

To retrieve an entity, modify it, and save it.

```php
$users = $repo->getRepository('users');
$user1 = $users->findByKey(1);
$user1->fill(['name' => 'your name']);
$users->save($user1);
```

#### relation (hasMany)

The `Repo` has 3 methods for relation: `hasOne`, `hasMany`, and `hasJoin`. 
The example `Users` repository uses the `hasMany` relation to 
another table, `posts` as;
 
```php
public function posts(EntityInterface $user) {
    return $this->repo->hasMany($this, 'posts', $user);
}
```

To use the relation, for example:

```php
// use generic repository for posts table...
$posts = $repo->getRepository('posts', ['post_id'], true);
```

create a posts repository (using a generic repository in this example), then:

```php
// prepare $user1 and relation to posts
$users = $repo->getRepository('users');
$user1 = $users->findByKey(1);

// get a list of posts
$relPosts  = $users->posts($user1); // hasMany relation.
$listPosts = $user1ToPosts->find();

// relate a new post to $user1.
$post  = $posts->create(['contents' => 'test relation'])
$relPosts->relate($post); // $post related to $user1.
$posts->save($post);          // save the post. 
```


Basic Usage
====

Repository
----

A repository is a gateway object to access a table in a database. 

It must implement `RepositoryInterface`. 

### RepositoryInterface

`WScore\Repository\Repository\RepositoryInterface` is an interface 
that define a repository for an entity. 

### Abstract Repository

`WScore\Repository\Repository\AbstractRepository` is a sample repository 
implementation used also as a generic repository. 

### Generic Repository

`WScore\Repository\Repository\Repository` is the generic implementation 
of a repository using `AbstractRepository`. 

```php
$posts = $repo->getRepository(
    string $tableName,    // database table name 
    array  $primaryKeys,  // primary keys in array, ex: ['id']
    bool   $autoIncrement // set true to use auto incremented id. 
);
```



Entity
----

### EntityInterface

The entity objects must implement `WScore\Repository\Entity\EntityInterface` 
so that repositories can retrieve data and primary keys from entities. 

`getPrimaryKeys()` returns an array of primary keys even if there is 
only one key. For convenient purpose, there are `getIdValue()` and 
`getIdName()` methods that return the primary key value and name 
if there is only one primary key. 

### AbstractEntity and Entity

`WScore\Repository\Entity\AbstractEntity` is a sample entity 
implementation used also as a generic entity. 
`WScore\Repository\Entity\Entity` is a generic implementation 
of an entity object using `AbstractEntity`. It serves as a 
default entity object class if not specified. 

### Active Record

it is possible to make the Entity active, 
just like an Active Pattern. 


Relations
====

Sample database. 

```sql
CREATE TABLE users (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name  VARCHAR(64) NOT NULL UNIQUE
);

CREATE TABLE posts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    users_id    INTEGER,
    contents    VARCHAR(256)
);

CREATE TABLE tags (
    tag_id      VARCHAR(32),
    tag         VARCHAR(64)
);

CREATE TABLE posts_tags (
    posts_tags_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    posts_post_id INTEGER,
    tags_tag_id VARCHAR(32)
);
```

HasOne
----

* uses primary keys.
* in case column name is different, use `$convert` array. 

```php
use WScore\Repository\Relations\HasOne;

new HasOne(
    $sourceRepo,   // repository object or repository name string
    $targetRepo,   // repository object or repository name string
    $convertArray  // if primary key names differs...
);
```

where `$sourceRepo` for `posts` and `$targetRepo` for `users` 
repositories, and $sourceEntity is `$post` entity. 


HasMany
----

* uses primary keys.
* in case column name is different, use `$convert` array. 

```php
use WScore\Repository\Relations\HasMany;

new HasMany(
    $sourceRepo,   // repository object or repository name string
    $targetRepo,   // repository object or repository name string
    $convertArray  // if primary key names differs...
);
```

where `$sourceRepo` for `users` and `$targetRepo` for `posts` 
repositories, and $sourceEntity is `$user` entity. 


Join Relation
----

Cross/Join, or Many-to-many relationship. 

Uses JoinRepositoryInterface and JoinRelationInterface.

default setup: 

* joining two tables: (table1 and table2)
* join table name: table1_table2, sorted by table name. 
* uses primary keys of table1 and table2. 
* keys in the join table should be {tableX}_{primaryKey}. 


Join, or many-to-many relation needs special attention 
because it requires another table (called `join_table`) 
to represents a relation. 

```php
use WScore\Repository\Relations\HasMany;

new HasJoin(
    $joinRepo   // repository object or repository name string
);
```

where `$joinRepo` is a repository for join table, which must 
implement `JoinRepositoryInterface`. To create a `$joinRepo` 
using a provided class, 

```php
new JoinRepository(
    $repo,
    'posts_tags', 
    'posts', 
    'tags'
);
```

where 'posts_tags' is a join table name, and 2 repositories 
that are joined. 

The current implementation of the `JoinRepository` assumes 
that two different tables are joined. 

Complex Relations
====

All of the relations must be related by using primary keys.


### Sample table. 

```sql
CREATE TABLE members (
    type  INTEGER NOT NULL,
    code  INTEGER NOT NULL,
    name        VARCHAR(64) NOT NULL UNIQUE,
    PRIMARY KEY (type, code)
);

CREATE TABLE orders (
    member_type INTEGER NOT NULL,
    member_code INTEGER NOT NULL,
    fee_year    INTEGER NOT NULL,
    fee_code    INTEGER NOT NULL,
    PRIMARY KEY (member_type, member_code, fee_year, fee_code)
);
```

HasOne
----

General code:

For the relation sample, 

```php
$repo->hasOne($ordersRepo, $memberRepo, $orderEntity, [
    'member_type' => 'type',
    'member_code' => 'code',
]);
```



HasMany
----

General code:

In case, the `id` has different as in the sample below, 
set `$convert` array. 

```php
$repo->hasMany($membersRepo, $ordersRepo, $memberEntity, [
    'type' => 'member_type', 
    'code' => 'member_code',
]);
```
        


HasJoin
----




To use a class as a repository, prepare the class and set 
factory in the container. 


More 
====

A generic repository. 
----

```php
$repo  = $c->get(Repo::class);
$users = $repo->getRepository('users', ['user_id'], true);
$user1 = $users->create(['name' => 'my name']);
$users->save($user1);
```

