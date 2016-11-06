WScore/Repository
=================

Yet-Another ORM for PHP based on (probably) a Repository Pattern; 
entities and Repositories are represented by different classes. 

Other than being able to read, create, update, delete, 
and relate entities, `WScore/Repository` has following features. 

* Use **different classes for DAO and Entity layer**. 
  You can use your own DAO object to implement some special requirements if necessary. 
* Work with **composite primary keys**. 
* Yet, it is easy to use.

On the other hand, it does not have followings. 

* **No eager loading**;
  instead, use `Assembly` object or other libraries. 
* **No caching of entities**; most of the operations results in database access. 
* No complex SQL construction. 

Under development. Not ready for production. 

Installation: `git clone https://github.com/asaokamei/wscore.repository`

### Separation of Layers

There are three layers. 

```
  EntityInterface　↔ RelationInterface
            ↑          ↕
        RepositoryInterface
                ↓
          QueryInterface
```

* The top layer is the `EntityInterface`, which is mostly 
  independent from the bottom layers but provides methods 
  necessary for bottom layers to work with. 
  `RelationInterface` relates entities using repositories, 
  which has some dependencies on bottom layer. 
* The `RepositoryInterface` layer is a gateway to database tables 
  that persist the entities. 
* The `QueryInterface` layer is at the bottom of the layers 
  responsible of querying database. 

There is a `Repo` class which serves as a container as well as 
a factory for repositories and relations. 
Requires a container that implements `ContainerInterface`. 

There is `JoinRelationInterface` for many-to-many (join) relation.

Sample Code
===========

Preparation
----

Requires some preparation to use this ORM. 

### sample database

Here's a sample database table, `users`. 

```sql
CREATE TABLE users (
    user_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(64) NOT NULL UNIQUE
);
```

### `Users` repository

Create a repository for a database table, such as;

```php
use WScore\Repository\Repo;
use WScore\Repository\Repository\AbstractRepository;

class Users extends AbstractRepository
{
    protected $table = 'users';           // table name
    protected $primaryKeys = ['user_id']; // primary keys in array
    protected $useAutoInsertId = true;    // use auto-incremented ID.
}
```

The `AbstractRepository` serves as a convenient way to create 
a repository but any class can be served as repository which 
implements `WScore\Repository\Repository\RepositoryInterface`. 

### setting up `Repo` and a container

`WScore/Repository` uses a container that implements `ContainerInterface`. Set up a container 
(assuming that the container has `set` method). 

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

Although it is not necessary to inject `Repo` object into a repository, 
it is a handy class for managing repository as well as a factory 
for relation objects. The `Repo` can be constructed as;

```php
$repo = new Repo($container); // inject the container. 
```


Working with Repository and Entity
----

Entity objects implementing `EntityInterface` are constructed from the fetched result. `WScore\Repository\Entity\Entity` class is used as a default class for all repository of `AbstractRepository`).  

### create and save

To create an entity and save it: 

```php
$users = $container->get('users');
$user1 = $users->create(['name' => 'my name']);
$id    = $users->save($user1); // should return inserted id.
```

### retrieve, modify, and save. 

To retrieve an entity, modify it, and save it.

```php
$users = $container->get('users');
$user1 = $users->findByKey(1);
$user1->fill(['name' => 'your name']);
$users->save($user1);
```

Relation
--------

There are 3 relations: `HasOne`, `HasMany`, and `JoinBy`. 

### sample database

Add another database table, `posts`, that is related to the `users`.

```sql
CREATE TABLE posts (
    post_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    contents    VARCHAR(256)
);
```

Also create `Posts` class and set it in the container. 

```php
class Posts extends AbstractRepository
{
    protected $table = 'posts';           // table name
    protected $primaryKeys = ['post_id']; // primary keys in array
    protected $useAutoInsertId = true;    // use auto-incremented ID.
}
$c->set('posts', function(ContainerInterface $c) {
    new new Posts($c->get(Repo::class));
});
```


### user hasMany posts

Let's change the `Users` repository by adding a `hasMany` relation.

```php
class Users extends AbstractRepository
{
    // ... from previous code ...
    /**
     * @return RelationInterface
     */
    public function posts() 
    {
        return $this->repo->hasMany($this, 'posts');
    }
}
```

`Repo` has helper methods to create relation objects. 

### relating entities

Now try to related users and posts. 
First, create repositories for `users` and `posts`. 
This example uses generic repository for `posts` table. 

```php
$users = $container->get('users');
$posts = $container->get('posts');
```

then:

```php
// prepare $user1 and relation to posts
$user1 = $users->findByKey(1);

// get a list of posts
$user1Posts = $user1->posts()->find();

// relate a new post to $user1.
$newPost  = $posts->create(['contents' => 'test relation'])
$user1->posts()->relate($newPost);
$newPost->save(); // save the post. 
```


Customizing Repository and Entity
====

EntityInterface
----

The entity objects must implement `WScore\Repository\Entity\EntityInterface` 
so that repositories can retrieve data and primary keys from entities. 

`getPrimaryKeys()` returns an array of primary keys even if there is 
only one key. For convenient purpose, there are `getIdValue()` and 
`getIdName()` methods that return the primary key value and name 
if there is only one primary key. 

### AbstractEntity and Entity

`WScore\Repository\Entity\AbstractEntity` is a sample entity 
implementation used also as a generic entity class,  
`WScore\Repository\Entity\Entity`. It serves as a 
default entity object class if not specified. 

To use your own entity class based on the `AbstractEntity`, 
extend the class while setting properties. 

```php
class MyEntity extends AbstractEntity
{
    protected $valueObjectClasses = [
        'created_at' => \DateTimeImmutable::class,
        'status' => function($value) {
            return ValueOfStatus::forge($value);
        },
    ];
	/**
     * Entity constructor.
     *
     * @param string $table
     * @param array $primaryKeys
     */
    public function __construct($table, array $primaryKeys)
    {
        parent::__construct($table, $primaryKeys);
    }
}
```

#### constructor

The current repository passes `$table`, `$primaryKey`, and `$repository`, to the entity's constructor. 

Make sure you call parent constructor in the constructor with `$table` and `$primaryKeys`; it sets some flags to manage its status used during the fetch operation in PDOStatement. 

#### value object

`$valueObjectClasses` is used to construct a value object when accessed via `get` method. Provide 

* a class name, if it can be constructed by `new Class($value)`, or 
* a callable factory. 


### Active Record

it is possible to make the Entity active, 
just like an Active Pattern. 

### isFetched?

`AbstractEntity` uses several internal flags to manage the status or origins of the entity. 

* `$isFetchDone`: a flag that is set to true **in the constructor**. 
  Used to set properties during PDO's fetchObject. 
* `$isFetched`: a flag that is set to true if fetched from PDO. 
* `isFetched()`: a method returns if the entity is fetched from database. Uses the `$isFetched` flag. 
* `setPrimaryKeyOnCreatedEntity()`: a method to set primary keys at inserting a new entity into database. This method is used if repository's `$autoInsertedId` flag is true (i.e. using auto-incremented id). This method also sets `$isFetched` flag to true. 


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

Extend the `AbstractRepostory` while overriding important properties 
of the database table. 

```php
abstract class AbstractRepository implements RepositoryInterface
{
    protected $table;              // table name
    protected $primaryKeys = [];   // primary keys in array. 
    protected $columnList = [];    // list of columns. can be empty.
    protected $entityClass = Entity::class; // entity class name.
    protected $timeStamps = [      // if any
        'created_at' => null,      // sets datetime at creation
        'updated_at' => null,      // sets datetime at modification
    ];
    protected $timeStampFormat = 'Y-m-d H:i:s'; // format of datetime
    protected $useAutoInsertId = false;  // set to true for auto-increment id. 
    
    ...
}
```

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

