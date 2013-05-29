DoctrineORMServiceProvider
--------------------------

The DoctrineORMServiceProvider provides integration with the Doctrine ORM

Installation
------------

``` json
{
    "require": {
        "kzykhys/doctrine-orm-service-provider":"dev-master"
    }
}
```

Parameters
----------

* **orm.cache.dir**: The cache directory to store the doctrine cache data.
* **orm.entity.path**: Array of directory.
* **orm.proxy.dir**: The directory to store proxy classes.
* **orm.proxy.namespace**: The namespace of each proxy classes.

Services
--------

* **orm.em**: Entity Manager for Doctrine, instance of `Doctrine\ORM\EntityManager`.
* **orm.schema_tool**: instance of `Doctrine\ORM\Tools\SchemaTool`.

Usage
-----

``` php
<?php

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use KzykHys\Silex\Provider\DoctrineORM\DoctrineORMServiceProvider;

$app = new Silex\Application();
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => '...'
));
$app->register(new DoctrineORMServiceProvider(), array(
    'orm.cache.dir'       => __DIR__ . '/app/cache/doctrine/orm',
    'orm.entity.path'     => array(__DIR__ . '/path/to/entity_dir'),
    'orm.proxy.dir'       => __DIR__ . '/app/cache/doctrine/proxies',
    'orm.proxy.namespace' => 'Your\Namespace\Orm\Proxies'
));

$app->get('/new', function (Application $app) {
    $user = new User();

    $app['orm.em']->persist($user);
    $app['orm.em']->flush();
});
```