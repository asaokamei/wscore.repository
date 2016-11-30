WScore/Repository
=================

Yet-Another ORM package for PHP which is (probably) based on 
a Repository Pattern; entities and data-access-objects are 
represented by different classes. 

Other than being able to read, create, update, delete, 
and relate entities, `WScore/Repository` has following features. 

* Uses **different classes for DAO and Entity layer**. 
  You can use your own DAO object to implement some special requirements if necessary. 
* Works very well with **composite primary keys**. 
* Behaves similar to ActiveRecord.

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
    name        VARCHAR(64) NOT NULL
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

### setting up `Repo` container

Set up `Repo` class, that is a container for repositories, 
as well as some useful factories. 

```php
$repo = new Repo();
$repo->set(PDO::class, function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});
$repo->set('users', function(Repo $repo) {
    new new Users($repo);
});
```

Working with Repository and Entity
----

Entity objects implementing `EntityInterface` are constructed from the fetched result. `WScore\Repository\Entity\Entity` class is used as a default class for all repository of `AbstractRepository`).  

### create and save

To create an entity and save it: 

```php
$users = $repo->get('users');
$user1 = $users->create(['name' => 'my name']);
$id    = $users->save($user1); // should return inserted id.
```

### retrieve, modify, and save. 

To retrieve an entity, modify it, and save it.

```php
$users = $repo->get('users');
$user1 = $users->findByKey(1);
$user1->fill(['name' => 'your name']);
$users->save($user1);
```

Relating Entities
--------

There are 3 relations: `BelongsTo`, `HasMany`, and `JoinBy`. 

### sample database

Add another database table, `posts`, that is related to the `users`.

```sql
CREATE TABLE posts (
    post_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    contents   VARCHAR(256)
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


### user `hasMany` posts

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

### relating `users` and `posts`

Now try to related users and posts. 
First, create repositories for `users` and `posts`. 
This example uses generic repository for `posts` table. 

```php
$users = $repo->get('users');
$posts = $repo->get('posts');
```

then:

```php
// prepare $user1 and relation to posts
$user1 = $users->findByKey(1);

// get all of related posts
$user1Posts = $user1->posts;

// relate a new post to $user1.
$newPost  = $posts->create(['contents' => 'test relation'])
$user1->posts()->relate($newPost);
$newPost->save(); // save the post. 
```

### eager loading using Assembly 

`Assembly` is essentially a collection of entities with ability to load 
related entities _eagerly_. 

```php
$user12 = $users->collectFor(['user_id' => [1, 2]]); // collection of entity.
$user12->load('posts'); // load related entities using repository's posts() method. 

foreach($user12 as $user) {
    echo $user->name;
    foreach($user->posts as $post) {
        echo $post->content;
    }
}
```

Relations
====

There are `BelongsTo`, `HasMany`, and `Join` relations.

### sample database

```
    |users|    |posts  |    |posts_tags|    |tags|
    +-----+    +-------+    +----------|    +----|
    |id   |*--<|id     |*--<|post_id   |>--*|code|
    |name |    |user_id|    |tag_id    |    |tag |
               |content|    
```


BelongsTo
----

Use `BelongsTo` object to represent a relation when the from table 
contains the foreign key to other table. 

```php
use WScore\Repository\Relations\BelongsTo;

new BelongsTo(
    $fromRepo,     // repository to relate from 
    $toRepo,       // repository to relate to 
    $convertArray  // conversion of keys
);
```

where 

* `$fromRepo` for `posts` repository, 
* `$toRepo` for `users` repository, and 
* `$convertArray` is the key-map of `$fromRepo` to the `$toRepo`. 

For the example above, the `posts` table contains `user_id` as a 
foreign key to `users` table. To construct `BelongsTo` object, 
provide `$posts` as from repository, `$users` repository as to 
repository, and key map from `user_id` to `id`, such as; 

```php
$userToPost = new BelongsTo($posts, $users, ['user_id' => 'id']);
```

`$convertArray` maybe omitted if the primary keys of the `$toRepo` 
is used as foreign keys and the column names in both repositories 
are the same. 

`Repo` object has a convenient method to construct a `BelongsTo` object; 
for instance following code will convert the repository name to 
repository object.

```php
$repo->belongsTo('posts', 'users', ['user_id' => 'id']);
```

HasMany
----

Use `HasMany` object to represent one-to-many relation.

```php
use WScore\Repository\Relations\HasMany;

new HasMany(
    $fromRepo,     // repository to relate from 
    $toRepo,       // repository to relate to 
    $convertArray  // conversion of keys
);
```

* `$fromRepo` for `posts` repository, 
* `$toRepo` for `users` repository, and 
* `$convertArray` is the key-map of `$fromRepo` to the `$toRepo`. 

For the example above, the `users` table has many related `posts` 
records using the `users:id` as foreign key. To construct a `HasMany` 
object, provide `$users` as from repository, `$posts` as to repository, 
and key map from `id` to `user_id`, such as; 

```php
$userToPost = new HasMany($users, $posts, ['id' => 'user_id']);
```

`$convertArray` maybe omitted if the primary keys of the `$fromRepo` 
is used as foreign keys and the column names in both repositories 
are the same. 

`Repo` object has a convenient method to construct a `HasMany` object; 
for instance following code will convert the repository name to 
repository object.

```php
$repo->hasMany('users', 'posts', ['id' => 'user_id']);
```


Join Relation
----

Use `Join` object to represent many-to-many relation 
using a join (or cross) table. 

```php
new Join(
        RepositoryInterface $fromRepo,
        RepositoryInterface $toRepo,
        RepositoryInterface $joinRepo,
        array $from_convert = [],
        array $to_convert = []
);
```

* `$fromRepo` for `posts` repository, 
* `$toRepo` for `tags` repository,
* `$joinRepo` for 'posts_tags' repository,
* `$from_convert` is the key-map of `$fromRepo` to the `$joinRepo`. 
* `$to_convert` is the key-map of `$joinRepo` to the `$toRepo`. 

For the example above, the `users` entity is related to 
one `posts` entity by mapping`users:id` to `posts:user_id`. 

```php
$userToPost = new Join($posts, $tags, $posts_tags, 
    ['id' => 'post_id'], ['tag_id' => 'code']);
```

The `$from_convert` as well as `$to_convert` maybe omitted 
if the primary keys of the `$fromRepo` and `$toRepo` 
are used as foreign keys and the column names in all repositories 
are the same. 

`Repo` object has a convenient method to construct a `Join` object. 
If all conversion array can be omitted, and join table name is 
`[from table name]_[to table name]`, the shortest case 

```php
$repo->join('users', 'posts');
```

Assembly for Eager Loading
----

Assembly is a collection of entities that can eager load related entities. 

### repositories 

For `Assembly` to work, repositories must implement methods 
that returns `RelationInterface` objects.
Assumes the database table in the Relations section is used, 
and all repositories are setup to produce relation object as;

```php
$users->posts(); // returns hasMany object to posts table
$posts->tags();  // returns Join object to tags table
```

### loading related entities

First, create a list of users entities, 

```php
$users = new Collection($users);
$users->find(['id' => [1, 3]]);
```

then, load related entities using the repositories methods.  

```php
$users->load('posts')->load('tags');
```

### accessing related entities

Accessing the related entities as a property with the same 
name as the repository's method. 

```php
foreach($users as $user) {
    echo $user->name;
    foreach($user->posts as $post) {
        echo $post->content;
        foreach($post->tags as $tag) {
            echo $tag->tag;
        }
    }
}
```

Entity
====

An entity object represents a record of a database table. 

EntityInterface
----

The entity objects must implement `WScore\Repository\Entity\EntityInterface` 
so that repositories can retrieve data and primary keys from entities. 

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
     + @param RepositoryInterface $repository
     */
    public function __construct($table, array $primaryKeys, $repository)
    {
        parent::__construct($table, $primaryKeys, $repo);
    }
}
```

#### constructor

The current repository passes `$table`, `$primaryKey`, and 
`$repository`, to the entity's constructor. 

Make sure you call parent constructor in the constructor with 
at least `$table` and `$primaryKeys`; it sets some flags to 
manage its status used during the fetch operation in PDOStatement. 

#### primary keys

`getPrimaryKeys()` returns an array of primary keys even if there is 
only one key. For convenient purpose, there are `getIdValue()` and 
`getIdName()` methods that return the primary key value and name 
if there is only one primary key. 

#### value object

`$valueObjectClasses` is used to construct a value object when accessed via `get` method. Provide 

* a class name, if it can be constructed by `new Class($value)`, or 
* a callable factory. 


### Active Record

The entity behaves just like an ActiveRecord if its repository is 
passed as the third argument. 

#### save()

`save()` method inserts or update entity state to the database; 
based on the fetched state of the entity. 

```php
$entity->save();
```

which is the same as writing the following code.

```php
if ($entity->isFetched()) {
    $users->update($entity);
} else {
    $users->insert($entity);
}
```

#### `__call()` method and getting `RelationInterface` object

Entity objects call repository's methods using the magic method, `__call()`. 
If the returned value is `RelationInterface` objects, the entity sets 
itself to the relation object. 

As such, the entity for the `Users` class can do;

```php
$numberOfPosts = $user->posts()->count();
```

#### __get()

Accessing relation property will return the related entities, or properties. 

```php
$relatedPosts = $user->posts;
```

Repository
====

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

There is a generic repository by providing 

* table name, 
* primary keys, 
* and auto-incremented flag. 

It is possible to create a repository for the users table as;

```php
$repo  = $container->get(Repo::class);
$users = $repo->getRepository('users', ['user_id'], true);
$user1 = $users->findByKey(1);
$user1->fill(['name' => 'my name']);
$user1->save($user1);
```



